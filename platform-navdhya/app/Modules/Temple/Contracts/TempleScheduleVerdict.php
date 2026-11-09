<?php

namespace App\Modules\Temple\Contracts;

/**
 * Result verdict of a temple schedule and capacity evaluation (C4).
 */
final class TempleScheduleVerdict
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        private readonly bool $allowed,
        private readonly ?string $reason = null,
        private readonly int $effectiveCapacity = 0,
        private readonly array $details = []
    ) {
    }

    public static function allowed(int $effectiveCapacity = 1): self
    {
        return new self(true, null, $effectiveCapacity);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function rejected(string $reason, array $details = []): self
    {
        return new self(false, $reason, 0, $details);
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function effectiveCapacity(): int
    {
        return $this->effectiveCapacity;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
