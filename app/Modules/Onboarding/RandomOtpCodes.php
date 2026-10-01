<?php

namespace App\Modules\Onboarding;

final class RandomOtpCodes implements OtpCodeGenerator
{
    public function generate(int $length): string
    {
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }

        return $code;
    }

    public function lastFor(string $challengeId): ?string
    {
        return null;
    }

    public function record(string $challengeId, string $code): void
    {
    }
}
