<?php

namespace App\Modules\Onboarding;

/**
 * OTP code generation strategy: random in production, deterministic in
 * tests (derived from nothing secret — the hash is what protects codes).
 */
interface OtpCodeGenerator
{
    public function generate(int $length): string;

    public function record(string $challengeId, string $code): void;

    public function lastFor(string $challengeId): ?string;
}
