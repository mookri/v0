<?php

namespace App\Modules\Customers\Auth;

use Random\RandomException;

/**
 * Production OTP codes: cryptographically random digits.
 */
final class RandomOtpCodes implements OtpCodeGenerator
{
    /**
     * @throws RandomException
     */
    public function generate(int $length): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }

        return $code;
    }

    public function record(string $challengeId, string $code): void
    {
    }

    public function lastFor(string $challengeId): ?string
    {
        return null;
    }
}
