<?php

namespace App\Modules\Customers\Auth;

use App\Modules\Customers\Models\CustomerOtpChallenge;
use Carbon\CarbonImmutable;

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
        $recent = CustomerOtpChallenge::query()
            ->where('tenant_id', $tenantId)
            ->where('purpose', $purpose)
            ->where('identifier', $identifier)
            ->where('created_at', '>=', $now->subHour()->toDateTimeString())
            ->count();

        if ($recent >= self::MAX_PER_HOUR) {
            throw new OtpRateLimitedException();
        }

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

        return $challenge;
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
        $challenge = CustomerOtpChallenge::query()
            ->where('id', $challengeId)
            ->where('tenant_id', $tenantId)
            ->whereIn('purpose', $purposes)
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
    }
}
