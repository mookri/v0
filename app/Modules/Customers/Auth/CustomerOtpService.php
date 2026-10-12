<?php

namespace App\Modules\Customers\Auth;

use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Platform\Identity\Otp\EmailOtpDelivery;
use App\Platform\Identity\Otp\EmailOtpDeliveryProvider;
use App\Platform\Identity\Otp\LocalCodeProvider;
use App\Platform\Identity\Otp\NullEmailOtpDelivery;
use App\Platform\Identity\Otp\OtpAudienceRouter;
use App\Platform\Identity\Otp\OtpCaptchaRequiredException;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpPurposePolicy;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use App\Platform\Identity\Otp\PhoneVerificationProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Customer OTP issuance + verification (auth slice). Same policy as
 * onboarding's OtpService (6 digits, 600 s TTL, 3 attempts, 5/hour per
 * identifier+purpose) plus purpose scoping and an opaque payload (signup
 * intent travels with the challenge until verification creates the
 * account). Rate caps are DB-backed (REDIS-001 interim posture).
 *
 * Verification Fabric: send/match delegate to the
 * PhoneVerificationProvider port — this service keeps the entire state
 * machine (caps, supersession, TTL, attempt counting, uniform 401) and
 * dispatches verification on the row's own `provider` column. S48
 * routing resolves the sending provider from the purpose policy table
 * (config/otp-policy.php: audience + risk tier) through the audience
 * registry (config/otp-providers.php) — local everywhere by default,
 * firebase-<audience> once an environment selects it — and enforces
 * the audience's reCAPTCHA requirement before any remote send. The
 * email delivery port (NullEmailOtpDelivery or the mailer channel,
 * config-selected) runs only for email identifiers on local sends.
 */
final class CustomerOtpService
{
    public const CODE_LENGTH = 6;

    public const TTL_SECONDS = 600;

    public const MAX_ATTEMPTS = 3;

    public const MAX_PER_HOUR = 5;

    private readonly PhoneVerificationProvider $local;

    private readonly ?OtpProviderRegistry $providers;

    private readonly EmailOtpDeliveryProvider $emailDelivery;

    public function __construct(
        private readonly OtpCodeGenerator $codes,
        ?OtpProviderRegistry $providers = null,
        ?EmailOtpDeliveryProvider $emailDelivery = null,
    ) {
        $this->local = new LocalCodeProvider($codes);
        $this->providers = $providers;
        $this->emailDelivery = $emailDelivery ?? new NullEmailOtpDelivery();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function issue(
        string $tenantId,
        string $purpose,
        string $identifier,
        CarbonImmutable $now,
        array $payload = [],
        ?string $attestation = null,
    ): CustomerOtpChallenge {
        // M2/M3: one transaction serialised on the tenant row — a
        // concurrent resend waits here, then supersedes what the first
        // one inserted, so exactly one challenge per
        // (tenant, purpose, identifier) is ever active (C2/G1). The
        // hourly cap still counts every issuance: mark, not delete,
        // keeps superseded rows inside the window.
        return DB::connection('central')->transaction(function () use ($tenantId, $purpose, $identifier, $now, $payload, $attestation): CustomerOtpChallenge {
            DB::connection('central')->table('tenants')
                ->where('id', $tenantId)
                ->lockForUpdate()
                ->first();

            $recent = CustomerOtpChallenge::query()
                ->where('tenant_id', $tenantId)
                ->where('purpose', $purpose)
                ->where('identifier', $identifier)
                ->where('created_at', '>=', $now->subHour()->toDateTimeString())
                ->count();

            if ($recent >= self::MAX_PER_HOUR) {
                throw new OtpRateLimitedException();
            }

            CustomerOtpChallenge::query()
                ->where('tenant_id', $tenantId)
                ->where('purpose', $purpose)
                ->where('identifier', $identifier)
                ->whereNull('superseded_at')
                ->update(['superseded_at' => $now->toDateTimeString()]);

            // S48 routing: purpose -> audience + risk tier (config
            // otp-policy.php; unknown purposes fail closed), then
            // audience -> provider via otp-providers.php (local unless
            // an environment selects a remote one). The resolved name
            // lands on the row either way, so verify() keeps
            // dispatching on the row's own provenance.
            $policy = OtpPurposePolicy::resolve($purpose);
            $provider = $this->providerFor(OtpAudienceRouter::providerName($policy->audience));

            if ($provider->name() !== LocalCodeProvider::NAME
                && $policy->captchaRequired()
                && $attestation === null) {
                // Fail closed BEFORE any provider call: the rollback
                // leaves no supersession marks behind. A remote send
                // without its reCAPTCHA attestation is a typed domain
                // error (controllers map it to 422) — never a
                // downgrade to a channel that skips attestation.
                throw new OtpCaptchaRequiredException($policy->audience);
            }

            // Pre-generate the row id (identical ULID to what HasUlids
            // would assign) so the provider can record the send against
            // it.
            $id = (string) \Illuminate\Support\Str::ulid();
            $send = $provider->send(new OtpSendContext(
                challengeId: $id,
                identifier: $identifier,
                purpose: $purpose,
                codeLength: self::CODE_LENGTH,
                attestation: $attestation,
                audience: $policy->audience,
            ));

            if ($send->isEmpty()) {
                // A send with neither code nor provider ref is a
                // misconfigured provider — fail closed, no row.
                throw new OtpProviderUnavailableException('otp provider produced neither code nor provider_ref');
            }

            $challenge = new CustomerOtpChallenge();
            $challenge->forceFill([
                'id' => $id,
                'tenant_id' => $tenantId,
                'purpose' => $purpose,
                'identifier' => $identifier,
                'code_hash' => $send->code === null ? null : hash('sha256', $send->code),
                'provider' => $provider->name(),
                'provider_ref' => $send->providerRef,
                'status' => 'sent',
                'payload' => $payload === [] ? null : $payload,
                'expires_at' => $now->addSeconds(self::TTL_SECONDS)->toDateTimeString(),
                'attempts' => 0,
                'max_attempts' => self::MAX_ATTEMPTS,
            ]);
            $challenge->save();

            // Drill-only code log (config/customers.php): the only
            // channel codes surface through today. Off by default;
            // never enable in production. Remote sends expose no code,
            // so they never log here.
            if ($send->code !== null && config('customers.log_otp_codes') === true) {
                \Illuminate\Support\Facades\Log::info('OTP challenge issued (drill-only code log)', [
                    'challenge_id' => $id,
                    'code' => $send->code,
                ]);
            }

            // Email channel delivery port (S46: NullEmailOtpDelivery —
            // no channel bound yet, call site + contract in place).
            if ($send->code !== null && str_contains($identifier, '@')) {
                $this->emailDelivery->deliver(new EmailOtpDelivery(
                    to: $identifier,
                    code: $send->code,
                    challengeId: $id,
                    purpose: $purpose,
                    expiresAt: $now->addSeconds(self::TTL_SECONDS)->toDateTimeString(),
                ));
            }

            return $challenge;
        });
    }

    public function lastCodeFor(string $challengeId): ?string
    {
        return $this->codes->lastFor($challengeId);
    }

    /**
     * Single-use verification scoped to tenant + purpose: missing,
     * foreign-tenant, wrong-purpose, expired, exhausted, or mismatched
     * codes all return false (the controller collapses them to one 401 —
     * no oracle). Provider outages throw OtpProviderUnavailableException
     * instead — a dependency failure is never folded into the 401 set.
     */
    public function verify(
        string $tenantId,
        string $purpose,
        string $challengeId,
        string $code,
        CarbonImmutable $now,
    ): ?CustomerOtpChallenge {
        return $this->verifyForPurposes($tenantId, [$purpose], $challengeId, $code, $now);
    }

    /**
     * @param list<string> $purposes
     */
    public function verifyForPurposes(
        string $tenantId,
        array $purposes,
        string $challengeId,
        string $code,
        CarbonImmutable $now,
    ): ?CustomerOtpChallenge {
        // M3: one transaction per redemption with the challenge row
        // locked — concurrent submits serialise on the row, so exactly
        // one can consume it (C1), attempt bumps cannot be lost (C4),
        // and the loser re-reads committed absence and collapses to the
        // same 401. M2: superseded rows are dead in that same uniform
        // way (absent from the lock scope, no supersession oracle).
        return DB::connection('central')->transaction(function () use ($tenantId, $purposes, $challengeId, $code, $now): ?CustomerOtpChallenge {
            $challenge = CustomerOtpChallenge::query()
                ->where('id', $challengeId)
                ->where('tenant_id', $tenantId)
                ->whereIn('purpose', $purposes)
                ->whereNull('superseded_at')
                ->lockForUpdate()
                ->first();

            if ($challenge === null) {
                return null;
            }

            if ($challenge->expires_at <= $now->toDateTimeString() || $challenge->attempts >= $challenge->max_attempts) {
                $challenge->delete();

                return null;
            }

            $challenge->forceFill(['attempts' => $challenge->attempts + 1])->save();

            // Verification dispatches on the row's own provenance, not
            // on current config: a challenge issued under provider X is
            // always matched by provider X (hybrid fleets survive a
            // later routing change; unknown names fail closed).
            $outcome = $this->providerFor($challenge->provider)->verify(new OtpVerifyRequest(
                codeHash: $challenge->code_hash,
                providerRef: $challenge->provider_ref,
                code: $code,
            ));

            if ($outcome === OtpVerifyOutcome::Unavailable) {
                // Roll the attempt back with the transaction and surface
                // a typed dependency error — an outage is not a wrong
                // code and must not look like one.
                throw new OtpProviderUnavailableException('otp provider unavailable during verify');
            }

            if ($outcome !== OtpVerifyOutcome::Match) {
                return null;
            }

            $challenge->delete();

            return $challenge;
        });
    }

    /**
     * @throws OtpProviderUnavailableException for unregistered providers
     */
    private function providerFor(?string $name): PhoneVerificationProvider
    {
        if ($name === null || $name === LocalCodeProvider::NAME) {
            return $this->local;
        }

        if ($this->providers === null) {
            throw new OtpProviderUnavailableException(sprintf('otp provider [%s] is not registered', $name));
        }

        return $this->providers->get($name);
    }
}
