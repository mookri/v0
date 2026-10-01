<?php

namespace App\Modules\Onboarding;

use App\Modules\Onboarding\Models\OtpChallenge;
use Carbon\CarbonImmutable;

/**
 * OTP issuance + verification (onboarding stage 2 port). Interim home: the
 * identity token program owns challenge delivery later (SMS/voice vendor
 * behind a port); this service keeps policy + verification semantics, which
 * do not change when the sender does.
 */
final class OtpService
{
    public const CODE_LENGTH = 6;

    public const TTL_SECONDS = 600;

    public const MAX_ATTEMPTS = 3;

    public const MAX_PER_HOUR = 5;

    public function __construct(private readonly OtpCodeGenerator $codes)
    {
    }

    public function issue(string $identifier, CarbonImmutable $now): OtpChallenge
    {
        $hourAgo = $now->subHour()->toDateTimeString();
        $recent = OtpChallenge::query()
            ->where('identifier', $identifier)
            ->where('created_at', '>=', $hourAgo)
            ->count();

        if ($recent >= self::MAX_PER_HOUR) {
            throw new OtpRateLimitedException();
        }

        $code = $this->codes->generate(self::CODE_LENGTH);

        $challenge = new OtpChallenge();
        $challenge->forceFill([
            'identifier' => $identifier,
            'code_hash' => hash('sha256', $code),
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

    public function verify(string $challengeId, string $code, CarbonImmutable $now): bool
    {
        $challenge = OtpChallenge::query()->find($challengeId);

        if ($challenge === null) {
            return false;
        }

        if ($challenge->expires_at <= $now->toDateTimeString() || $challenge->attempts >= $challenge->max_attempts) {
            $challenge->delete();

            return false;
        }

        $challenge->forceFill(['attempts' => $challenge->attempts + 1])->save();

        $expected = $challenge->code_hash;
        $actual = hash('sha256', $code);

        if (! hash_equals($expected, $actual)) {
            return false;
        }

        $challenge->delete();

        return true;
    }
}
