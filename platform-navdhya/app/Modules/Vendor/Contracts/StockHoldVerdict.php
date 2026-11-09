<?php

namespace App\Modules\Vendor\Contracts;

/**
 * Value object representing the outcome of a stock reservation / hold request.
 */
final class StockHoldVerdict
{
    /**
     * @param bool $isSuccess
     * @param string|null $warehouseId
     * @param list<array{component_sku: string, quantity: int, warehouse_id: string}> $allocatedComponents
     * @param string|null $failureReason
     */
    public function __construct(
        public readonly bool $isSuccess,
        public readonly ?string $warehouseId = null,
        public readonly array $allocatedComponents = [],
        public readonly ?string $failureReason = null,
    ) {
    }

    /**
     * @param string $warehouseId
     * @param list<array{component_sku: string, quantity: int, warehouse_id: string}> $components
     */
    public static function success(string $warehouseId, array $components): self
    {
        return new self(true, $warehouseId, $components);
    }

    public static function failure(string $reason): self
    {
        return new self(false, null, [], $reason);
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }
}
