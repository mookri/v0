<?php

namespace App\Platform\Identity\Otp;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * The one OTP state machine every module shares (customers auth,
 * onboarding, MFA step-up, future purposes): declare a purpose in
 * config/otp-policy.php and inject this manager. It owns the whole
 * delivery policy — the compounding resend schedule (60s → 180s →
 * 540s, max 3 per challenge), the fresh-challenge back-off (no new
 * challenge while a live one can still deliver, then 10 min after
 * the last send), the rolling hourly cap, supersession, attempt
 * accounting and single-use consumption — and knows no vendor (send/
 * verify delegate to the PhoneVerificationProvider resolved per
 * audience) and no table (persistence delegates to the
 * OtpChallengeStore bound per module).
 *
 * Serialization: every mutation runs inside store->runLocked — a
 * concurrent issue/resend/verify on the same tenant waits on the row
 * lock, then sees committed state, so exactly one live challenge per
 * (tenant, purpose, identifier) exists and each redemption consumes
 * the row exactly once.
 *
 * Failure shapes: exhausted schedules/caps throw OtpRateLimitedException
 * (Retry-After included); provider faults throw
 * OtpProviderUnavailableException inside the transaction (attempt
 * bumps roll back — an outage never looks like a wrong code); every
 * credential-shaped miss collapses to NotFound/Mismatch for the
 * caller's uniform 401 (no oracle).
 */
final class OtpChallengeManager
{
    public function __construct(
        private readonly OtpChallengeStore $store,
        private readonly OtpProviderRegistry $providers,
    ) {
    }

    /**
     * Issue a fresh challenge for (tenant, purpose, identifier),
     * superseding any live one. Identity of the gate: while a live
     * challenge exists the client gets 429 until it is dead AND 10
     * minutes past its last send — resends are the intended path, so
     * SMS budget cannot be re-armed by restarting.
     *
     * @param  array<string, mixed>  $payload  opaque intent carried to
     *                                         the consumer after verification
     *                                         (e.g. signup profile)
     *
     * @throws OtpRateLimitedException with retryAfterSeconds
     * @throws OtpPurposeUnknownException when the purpose has no policy
     * @throws OtpProviderUnavailableException when the provider is misconfigured
     */
    public function issue(
        string $tenantId,
        string $purpose,
        string $identifier,
        CarbonImmutable $now,
        array $payload = [],
    ): StoredOtpChallenge {
        $policy = OtpPurposePolicy::resolve($purpose);
        $delivery = OtpDeliveryPolicy::load();

        return $this->store->runLocked($tenantId, function () use ($tenantId, $purpose, $identifier, $now, $payload, $policy, $delivery): StoredOtpChallenge {
            $live = $this->store->findLatestLive($tenantId, $purpose, $identifier);

            if ($live !== null) {
                $blockUntil = $live->expiresAt
                    ->max($live->lastSentAt?->addSeconds($delivery->freshRetryAfterSeconds) ?? $live->expiresAt);

                if ($now->lessThan($blockUntil)) {
                    throw new OtpRateLimitedException(self::retryAfter($now, $blockUntil));
                }
            }

            // Rolling-hour backstop (the gate above is the primary
            // control). Retry-After is the full window: conservative by
            // design — this branch only fires under hammering.
            $fresh = $this->store->countCreatedSince($tenantId, $purpose, $identifier, $now->subHour());
            if ($fresh >= $delivery->freshMaxPerHour) {
                throw new OtpRateLimitedException(3600);
            }

            $this->store->supersedeLive($tenantId, $purpose, $identifier, $now);

            $provider = $this->providers->get(OtpAudienceRouter::providerName($policy->audience));

            // Pre-generate the row id (identical ULID HasUlids would
            // assign) so the provider can attribute the send against
            // the row that will hold it.
            $id = (string) Str::ulid();
            $send = $provider->send(new OtpSendContext(
                challengeId: $id,
                identifier: $identifier,
                purpose: $purpose,
                codeLength: 6,
                audience: $policy->audience,
            ));

            if ($send->isEmpty()) {
                // A send with neither code nor provider ref is a
                // misconfigured provider — fail closed, no row.
                throw new OtpProviderUnavailableException('otp provider produced neither code nor provider_ref');
            }

            $challenge = new StoredOtpChallenge(
                id: $id,
                tenantId: $tenantId,
                purpose: $purpose,
                identifier: $identifier,
                codeHash: $send->code === null ? null : hash('sha256', $send->code),
                provider: $provider->name(),
                providerRef: $send->providerRef,
                status: 'sent',
                payload: $payload,
                createdAt: $now,
                expiresAt: $now->addSeconds($delivery->ttlSeconds),
                attempts: 0,
                maxAttempts: $delivery->maxAttempts,
                resendCount: 0,
                lastSentAt: $now,
            );
            $this->store->insert($challenge);

            Event::dispatch(new OtpChallengeIssued($tenantId, $purpose, $id, $identifier, $provider->name()));

            return $challenge;
        });
    }

    /**
     * Grant one resend on a live challenge: validate the compounding
     * schedule server-side (60s → 180s → 540s since the previous
     * send), bump the row, and only then hand the caller the channel
     * code for the widget's retryOtp. Returns null when the challenge
     * is gone (unknown, superseded, expired, exhausted or already
     * token-verified — held rows are past the send phase) and throws
     * OtpRateLimitedException when the schedule says wait.
     *
     * @param  list<string>|string  $purposes
     *
     * @throws OtpRateLimitedException with retryAfterSeconds
     */
    public function resend(
        string $tenantId,
        array|string $purposes,
        string $challengeId,
        CarbonImmutable $now,
    ): ?OtpResendResult {
        $purposes = (array) $purposes;
        $delivery = OtpDeliveryPolicy::load();

        return $this->store->runLocked($tenantId, function () use ($tenantId, $purposes, $challengeId, $now, $delivery): ?OtpResendResult {
            $challenge = $this->store->findLiveForUpdate($tenantId, $purposes, $challengeId);

            if ($challenge === null || $challenge->status !== 'sent') {
                return null;
            }

            if ($challenge->expiresAt <= $now || $challenge->attempts >= $challenge->maxAttempts) {
                $this->store->delete($challenge->id);

                return null;
            }

            $freshGateAt = $challenge->lastSentAt?->addSeconds($delivery->freshRetryAfterSeconds) ?? $challenge->expiresAt;

            if ($challenge->resendCount >= $delivery->resendMax) {
                // Schedule exhausted: the next code is a fresh
                // challenge, gated 10 min after the last send.
                throw new OtpRateLimitedException(self::retryAfter($now, $freshGateAt));
            }

            $nextAllowed = $delivery->nextResendAllowedAt($challenge->lastSentAt ?? $challenge->createdAt, $challenge->resendCount)
                ?? $freshGateAt;

            if ($now->lessThan($nextAllowed)) {
                throw new OtpRateLimitedException(self::retryAfter($now, $nextAllowed));
            }

            $challenge = $challenge->with([
                'resendCount' => $challenge->resendCount + 1,
                'lastSentAt' => $now,
            ]);
            $this->store->update($challenge);

            $channel = $delivery->channelFor($challenge->resendCount - 1);

            Event::dispatch(new OtpChallengeResent($tenantId, $challenge->purpose, $challenge->id, $challenge->identifier, $channel, $challenge->resendCount));

            return new OtpResendResult(
                channel: $channel,
                channels: $delivery->resendChannels,
                resendsLeft: $delivery->resendMax - $challenge->resendCount,
                nextAllowedAt: $delivery->nextResendAllowedAt($now, $challenge->resendCount),
            );
        });
    }

    /**
     * Redeem one attempt: lock the live row, bump attempts, let the
     * row's own provider judge the token. Held (holdOnMatch) keeps the
     * row at `token_verified` for a second factor — it must then be
     * finished through consumeVerified, never re-verified.
     *
     * @param  list<string>  $purposes
     *
     * @throws OtpProviderUnavailableException (the transaction rolls
     *         the attempt bump back — outages never burn attempts)
     */
    public function verify(
        string $tenantId,
        array $purposes,
        string $challengeId,
        string $token,
        CarbonImmutable $now,
        bool $holdOnMatch = false,
    ): OtpVerifyResult {
        return $this->store->runLocked($tenantId, function () use ($tenantId, $purposes, $challengeId, $token, $now, $holdOnMatch): OtpVerifyResult {
            $challenge = $this->store->findLiveForUpdate($tenantId, $purposes, $challengeId);

            if ($challenge === null) {
                return OtpVerifyResult::notFound();
            }

            if ($challenge->expiresAt <= $now || $challenge->attempts >= $challenge->maxAttempts) {
                $this->store->delete($challenge->id);

                return OtpVerifyResult::notFound();
            }

            $challenge = $challenge->with(['attempts' => $challenge->attempts + 1]);
            $this->store->update($challenge);

            // Dispatch on the row's own provenance, not current config:
            // a challenge issued under provider X is always judged by
            // provider X (unknown names fail closed).
            $provider = $this->providers->get((string) $challenge->provider);

            $outcome = $provider->verify(new OtpVerifyRequest(
                codeHash: $challenge->codeHash,
                providerRef: $challenge->providerRef,
                code: $token,
                identifier: $challenge->identifier,
            ));

            if ($outcome === OtpVerifyOutcome::Unavailable) {
                throw new OtpProviderUnavailableException('otp provider unavailable during verify');
            }

            if ($outcome !== OtpVerifyOutcome::Match) {
                if ($challenge->attempts >= $challenge->maxAttempts) {
                    $this->store->delete($challenge->id);
                }

                Event::dispatch(new OtpChallengeRejected($tenantId, $challenge->purpose, $challenge->id, $challenge->attempts));

                return OtpVerifyResult::mismatch($challenge);
            }

            if ($holdOnMatch) {
                $challenge = $challenge->with(['status' => 'token_verified']);
                $this->store->update($challenge);

                return OtpVerifyResult::held($challenge);
            }

            $this->store->delete($challenge->id);
            Event::dispatch(new OtpChallengeConsumed($tenantId, $challenge->purpose, $challenge->id, $challenge->identifier));

            return OtpVerifyResult::match($challenge);
        });
    }

    /**
     * Finish a held challenge after its second factor (TOTP verified
     * by the caller). Only a row parked at `token_verified` by verify
     * (holdOnMatch) can be consumed — plain `sent` rows never qualify,
     * so this cannot bypass the token step. The held row's remaining
     * TTL bounds the second factor; second-factor lockout is the
     * caller's (MFA layer's) concern, not the schedule's.
     *
     * @param  list<string>  $purposes
     */
    public function consumeVerified(
        string $tenantId,
        array $purposes,
        string $challengeId,
        CarbonImmutable $now,
    ): ?StoredOtpChallenge {
        return $this->store->runLocked($tenantId, function () use ($tenantId, $purposes, $challengeId, $now): ?StoredOtpChallenge {
            $challenge = $this->store->findLiveForUpdate($tenantId, $purposes, $challengeId, status: 'token_verified');

            if ($challenge === null) {
                return null;
            }

            if ($challenge->expiresAt <= $now) {
                $this->store->delete($challenge->id);

                return null;
            }

            $this->store->delete($challenge->id);
            Event::dispatch(new OtpChallengeConsumed($tenantId, $challenge->purpose, $challenge->id, $challenge->identifier));

            return $challenge;
        });
    }

    /**
     * Inspect a held challenge that is at `token_verified` without consuming it.
     *
     * @param  list<string>  $purposes
     */
    public function findVerified(
        string $tenantId,
        array $purposes,
        string $challengeId,
        CarbonImmutable $now,
    ): ?StoredOtpChallenge {
        return $this->store->runLocked($tenantId, function () use ($tenantId, $purposes, $challengeId, $now): ?StoredOtpChallenge {
            $challenge = $this->store->findLiveForUpdate($tenantId, $purposes, $challengeId, status: 'token_verified');

            if ($challenge === null) {
                return null;
            }

            if ($challenge->expiresAt <= $now) {
                $this->store->delete($challenge->id);

                return null;
            }

            return $challenge;
        });
    }

    private static function retryAfter(CarbonImmutable $now, CarbonImmutable $until): int
    {
        return max(1, (int) ceil($now->diffInSeconds($until)));
    }
}
