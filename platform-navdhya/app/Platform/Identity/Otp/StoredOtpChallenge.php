<?php

namespace App\Platform\Identity\Otp;

use Carbon\CarbonImmutable;

/**
 * Immutable view of one OTP challenge row, shaped for OtpChallengeManager.
 * Store-agnostic on purpose: the manager never touches Eloquent (Kernel
 * never imports Modules), so customer (central) and onboarding (tenant)
 * tables look identical to the policy machine.
 */
final readonly class StoredOtpChallenge
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $purpose,
        public string $identifier,
        public ?string $codeHash,
        public ?string $provider,
        public ?string $providerRef,
        public string $status,
        public array $payload,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $expiresAt,
        public int $attempts,
        public int $maxAttempts,
        public int $resendCount,
        public ?CarbonImmutable $lastSentAt,
    ) {
    }

    public function isLive(): bool
    {
        return $this->status !== 'consumed';
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function with(array $overrides): self
    {
        return new self(
            id: $overrides['id'] ?? $this->id,
            tenantId: $overrides['tenantId'] ?? $this->tenantId,
            purpose: $overrides['purpose'] ?? $this->purpose,
            identifier: $overrides['identifier'] ?? $this->identifier,
            codeHash: $overrides['codeHash'] ?? $this->codeHash,
            provider: $overrides['provider'] ?? $this->provider,
            providerRef: $overrides['providerRef'] ?? $this->providerRef,
            status: $overrides['status'] ?? $this->status,
            payload: $overrides['payload'] ?? $this->payload,
            createdAt: $overrides['createdAt'] ?? $this->createdAt,
            expiresAt: $overrides['expiresAt'] ?? $this->expiresAt,
            attempts: $overrides['attempts'] ?? $this->attempts,
            maxAttempts: $overrides['maxAttempts'] ?? $this->maxAttempts,
            resendCount: $overrides['resendCount'] ?? $this->resendCount,
            lastSentAt: $overrides['lastSentAt'] ?? $this->lastSentAt,
        );
    }
}
