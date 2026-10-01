<?php

namespace App\Modules\Customers\Auth;

/**
 * Deterministic OTP codes for tests: sequential codes, retrievable per
 * challenge (mirrors onboarding's SeededOtpCodes — owned here per the
 * module-autonomy note on OtpCodeGenerator).
 */
final class SeededOtpCodes implements OtpCodeGenerator
{
    /** @var array<string, string> */
    private array $issued = [];

    private int $seq = 0;

    public function generate(int $length): string
    {
        $this->seq++;

        return str_pad((string) (100000 + ($this->seq % 900000)), $length, '0', STR_PAD_LEFT);
    }

    public function record(string $challengeId, string $code): void
    {
        $this->issued[$challengeId] = $code;
    }

    public function lastFor(string $challengeId): ?string
    {
        return $this->issued[$challengeId] ?? null;
    }
}
