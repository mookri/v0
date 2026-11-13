<?php

namespace App\Modules\Catalog\Domain\Pricing;

use App\Modules\Catalog\Models\CatalogPrice;

/**
 * Resolved price value object (F4).
 */
final class ResolvedPrice
{
    public function __construct(
        public readonly CatalogPrice $price,
        public readonly int $priceMinor,
        public readonly string $rule,
        public readonly ?string $packageTier = null,
        public readonly ?string $qualificationTier = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'price_id' => (string) $this->price->getKey(),
            'sku' => $this->price->sku,
            'price_minor' => $this->priceMinor,
            'currency' => $this->price->currency,
            'market' => $this->price->market,
            'effective_rule' => $this->rule,
            'package_tier' => $this->packageTier,
            'qualification_tier' => $this->qualificationTier,
        ];
    }
}

