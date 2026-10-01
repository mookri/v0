<?php

namespace App\Modules\Customers\Auth;

/**
 * OTP code generation port (module autonomy: intentionally parallel to
 * onboarding's port rather than a cross-module import — deptrac allows
 * cross-module use only via Contracts, and OTP generation is small
 * enough to own twice).
 */
interface OtpCodeGenerator
{
    public function generate(int $length): string;

    public function record(string $challengeId, string $code): void;

    public function lastFor(string $challengeId): ?string;
}
