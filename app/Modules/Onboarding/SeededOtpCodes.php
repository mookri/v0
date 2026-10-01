<?php

namespace App\Modules\Onboarding;

final class SeededOtpCodes implements OtpCodeGenerator
{
    /** @var array<string, string> */
    private array $issued = [];

    private int $seq = 0;

    public function generate(int $length): string
    {
        $this->seq++;
        $code = str_pad((string) (100000 + ($this->seq % 900000)), $length, '0', STR_PAD_LEFT);

        return $code;
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
