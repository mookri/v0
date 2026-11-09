<?php

namespace App\Modules\Pandit\Contracts;

/**
 * Result verdict of a pandit schedule availability evaluation.
 */
final class ScheduleVerdict
{
    /**
     * @param  array<string, mixed>  $conflicts
     */
    public function __construct(
        private readonly bool $available,
        private readonly ?string $reason = null,
        private readonly array $conflicts = [],
    ) {
    }

    public static function available(): self
    {
        return new self(true);
    }

    /**
     * @param  array<string, mixed>  $conflicts
     */
    public static function unavailable(string $reason, array $conflicts = []): self
    {
        return new self(false, $reason, $conflicts);
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    /**
     * @return array<string, mixed>
     */
    public function conflicts(): array
    {
        return $this->conflicts;
    }
}
