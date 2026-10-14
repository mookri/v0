<?php

namespace App\Modules\Customers\Auth;

use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpResendResult;
use App\Platform\Identity\Otp\OtpVerifyResult;
use App\Platform\Identity\Otp\StoredOtpChallenge;
use Carbon\CarbonImmutable;

/**
 * Thin façade over OtpChallengeManager + CustomerChallengeStore
 * keeping method signatures for the Customers module and test suite.
 */
class CustomerOtpService
{
    private OtpChallengeManager $manager;

    public function __construct(
        ?object $codeGenerator = null,
        ?OtpProviderRegistry $registry = null,
        ?object $emailDelivery = null,
    ) {
        $this->manager = app(OtpChallengeManager::class);
    }

    public function issue(
        string $tenantId,
        string $purpose,
        string $identifier,
        CarbonImmutable $now,
        array $payload = [],
        ?string $attestation = null,
    ): CustomerOtpChallenge|StoredOtpChallenge {
        $challenge = $this->manager->issue($tenantId, $purpose, $identifier, $now, $payload);
        $model = CustomerOtpChallenge::query()->find($challenge->id);

        return $model ?? $challenge;
    }

    public function resend(
        string $tenantId,
        array|string $purposes,
        string $challengeId,
        CarbonImmutable $now,
    ): ?OtpResendResult {
        return $this->manager->resend($tenantId, $purposes, $challengeId, $now);
    }

    public function verify(
        string $tenantId,
        array|string $purposes,
        string $challengeId,
        string $token,
        CarbonImmutable $now,
        bool $holdOnMatch = false,
    ): OtpVerifyResult {
        return $this->manager->verify($tenantId, (array) $purposes, $challengeId, $token, $now, $holdOnMatch);
    }

    public function consumeVerified(
        string $tenantId,
        array|string $purposes,
        string $challengeId,
        CarbonImmutable $now,
    ): ?StoredOtpChallenge {
        return $this->manager->consumeVerified($tenantId, (array) $purposes, $challengeId, $now);
    }
}
