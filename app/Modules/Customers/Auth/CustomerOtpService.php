<?php

namespace App\Modules\Customers\Auth;

use App\Modules\Customers\Models\CustomerOtpChallenge;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Customer OTP issuance + verification (auth slice). Same policy as
 * onboarding's OtpService (6 digits, 600 s TTL, 3 attempts, 5/hour per
 * identifier+purpose) plus purpose scoping and an opaque payload (signup
 * intent travels with the challenge until verification creates the
 * account). Rate caps are DB-backed (REDIS-001 interim posture).
 */
final class CustomerOtpService
{
    public const CODE_LENGTH = 6;

    public const TTL_SECONDS = 600;

    public const MAX_ATTEMPTS = 3;

    public const MAX_PER_HOUR = 5;

    public function __construct(private readonly OtpCodeGenerator $codes)
    {
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
    ): CustomerOtpChallenge {
        // M2/M3: one transaction serialised on the tenant row — a
        // concurrent resend waits here, then supersedes what the first
        // one inserted, so exactly one challenge per
        // (tenant, purpose, identifier) is ever active (C2/G1). The
        // hourly cap still counts every issuance: mark, not delete,
        // keeps superseded rows inside the window.
        return DB::connection('central')->transaction(function () use ($tenantId, $purpose, $identifier, $now, $payload): CustomerOtpChallenge {
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

            $code = $this->codes->generate(self::CODE_LENGTH);

            $challenge = new CustomerOtpChallenge();
            $challenge->forceFill([
                'tenant_id' => $tenantId,
                'purpose' => $purpose,
                'identifier' => $identifier,
                'code_hash' => hash('sha256', $code),
                'payload' => $payload === [] ? null : $payload,
                'expires_at' => $now->addSeconds(self::TTL_SECONDS)->toDateTimeString(),
                'attempts' => 0,
                'max_attempts' => self::MAX_ATTEMPTS,
            ]);
            $challenge->save();
            $this->codes->record((string) $challenge->getKey(), $code);

            // Drill-only code log (config/customers.php): no delivery channel
            // exists outside tests yet, so local/staging drills read the code
            // from the log. Off by default; never enable in production.
            if (config('customers.log_otp_codes') === true) {
                \Illuminate\Support\Facades\Log::info('OTP challenge issued (drill-only code log)', [
                    'challenge_id' => (string) $challenge->getKey(),
                    'code' => $code,
                ]);
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
     * no oracle).
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

            if (! hash_equals($challenge->code_hash, hash('sha256', $code))) {
                return null;
            }

            $challenge->delete();

            return $challenge;
        });
    }
}
