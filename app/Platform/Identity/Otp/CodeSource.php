<?php

namespace App\Platform\Identity\Otp;

/**
 * OTP code generation seam (Kernel view of the module-local generator
 * ports). Customers/Auth and Onboarding each own an intentionally
 * parallel generator interface — module autonomy, deptrac allows
 * cross-module use only via Contracts, and OTP generation is small
 * enough to own twice — and both narrow into this Kernel interface so
 * Kernel-level providers (LocalCodeProvider) can wrap any of them
 * without Kernel importing a module (doc 01 §7).
 */
interface CodeSource
{
    public function generate(int $length): string;

    public function record(string $challengeId, string $code): void;

    public function lastFor(string $challengeId): ?string;
}
