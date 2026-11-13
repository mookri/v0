<?php

namespace App\Modules\Catalog\Domain\Pricing;

use App\Modules\Catalog\Models\CatalogPackageTier;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Platform\Tenancy\PlatformScope;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * 2-Axis Tiered Price Resolver (F4).
 *
 * Implements 5-step strict precedence resolution:
 * 1. Direct Pandit Override (pandit_id exact match)
 * 2. Two-Axis Match (package_tier_code AND qualification_tier_code)
 * 3. Package Tier Match (package_tier_code alone, qualification_tier_code null)
 * 4. Qualification Tier Match (qualification_tier_code alone, package_tier_code null)
 * 5. Base SKU Price (all axes null)
 */
final class TieredPriceResolver
{
    public const RULE_PANDIT_OVERRIDE = 'pandit_override';
    public const RULE_PACKAGE_AND_QUALIFICATION = 'package_and_qualification_tier';
    public const RULE_PACKAGE_TIER = 'package_tier';
    public const RULE_QUALIFICATION_TIER = 'qualification_tier';
    public const RULE_BASE_SKU = 'base_sku';

    /**
     * Resolve effective price for a service line.
     */
    public function resolve(
        string $sku,
        string $currency,
        string $market,
        DateTimeInterface $at,
        ?string $panditId = null,
        ?string $packageTier = null,
        ?string $qualificationTier = null,
        ?string $tenantId = null
    ): ?ResolvedPrice {
        $atString = CarbonImmutable::instance($at)->toDateTimeString();

        $resolveWithinContext = function () use ($sku, $currency, $market, $atString, $panditId, $packageTier, $qualificationTier, $tenantId): ?ResolvedPrice {
            $baseQuery = function () use ($sku, $currency, $market, $atString, $tenantId) {
                $q = CatalogPrice::query();
                if ($tenantId !== null) {
                    $q->where('tenant_id', $tenantId);
                }

                return $q->where('sku', $sku)
                ->where('currency', $currency)
                ->where('market', $market)
                ->where('effective_from', '<=', $atString)
                ->where(function ($query) use ($atString): void {
                    $query->whereNull('effective_to')->orWhere('effective_to', '>', $atString);
                })
                ->orderByDesc('effective_from');
        };

        // Step 1: Direct Pandit Override
        if ($panditId !== null) {
            $price = $baseQuery()->where('pandit_id', $panditId)->first();
            if ($price !== null) {
                return new ResolvedPrice(
                    price: $price,
                    priceMinor: (int) $price->price_minor,
                    rule: self::RULE_PANDIT_OVERRIDE,
                    packageTier: $packageTier,
                    qualificationTier: $qualificationTier
                );
            }
        }

        // Step 2: Two-Axis Match (Package Tier + Qualification Tier)
        if ($packageTier !== null && $qualificationTier !== null) {
            $price = $baseQuery()
                ->whereNull('pandit_id')
                ->where('package_tier_code', $packageTier)
                ->where('qualification_tier_code', $qualificationTier)
                ->first();

            if ($price !== null) {
                return new ResolvedPrice(
                    price: $price,
                    priceMinor: (int) $price->price_minor,
                    rule: self::RULE_PACKAGE_AND_QUALIFICATION,
                    packageTier: $packageTier,
                    qualificationTier: $qualificationTier
                );
            }
        }

        // Step 3: Package Tier Match
        if ($packageTier !== null) {
            $price = $baseQuery()
                ->whereNull('pandit_id')
                ->where('package_tier_code', $packageTier)
                ->whereNull('qualification_tier_code')
                ->first();

            if ($price !== null) {
                return new ResolvedPrice(
                    price: $price,
                    priceMinor: (int) $price->price_minor,
                    rule: self::RULE_PACKAGE_TIER,
                    packageTier: $packageTier,
                    qualificationTier: $qualificationTier
                );
            }
        }

        // Step 4: Qualification Tier Match
        if ($qualificationTier !== null) {
            $price = $baseQuery()
                ->whereNull('pandit_id')
                ->whereNull('package_tier_code')
                ->where('qualification_tier_code', $qualificationTier)
                ->first();

            if ($price !== null) {
                return new ResolvedPrice(
                    price: $price,
                    priceMinor: (int) $price->price_minor,
                    rule: self::RULE_QUALIFICATION_TIER,
                    packageTier: $packageTier,
                    qualificationTier: $qualificationTier
                );
            }
        }

        // Step 5: Base SKU Price
        $price = $baseQuery()
            ->whereNull('pandit_id')
            ->whereNull('package_tier_code')
            ->whereNull('qualification_tier_code')
            ->first();

        if ($price !== null) {
            return new ResolvedPrice(
                price: $price,
                priceMinor: (int) $price->price_minor,
                rule: self::RULE_BASE_SKU,
                packageTier: $packageTier,
                qualificationTier: $qualificationTier
            );
        }

            return null;
        };

        return $tenantId !== null
            ? PlatformScope::run('catalog_tiered_price_resolution', $resolveWithinContext, tenantId: $tenantId)
            : $resolveWithinContext();
    }

    /**
     * Resolves the complete package tier ladder for a given service.
     *
     * @return list<array{
     *     tier_code: string,
     *     tier_name: string,
     *     tier_description: string|null,
     *     sort: int,
     *     price_minor: int|null,
     *     effective_rule: string|null
     * }>
     */
    public function resolveLadder(
        string $sku,
        string $currency,
        string $market,
        DateTimeInterface $at,
        ?string $qualificationTier = null,
        ?string $tenantId = null
    ): array {
        $tiers = CatalogPackageTier::query()
            ->where('status', CatalogPackageTier::STATUS_ACTIVE)
            ->orderBy('sort')
            ->get();

        $ladder = [];
        foreach ($tiers as $tier) {
            $resolved = $this->resolve(
                sku: $sku,
                currency: $currency,
                market: $market,
                at: $at,
                panditId: null,
                packageTier: $tier->code,
                qualificationTier: $qualificationTier,
                tenantId: $tenantId
            );

            $ladder[] = [
                'tier_code' => $tier->code,
                'tier_name' => $tier->name,
                'tier_description' => $tier->description,
                'sort' => $tier->sort,
                'price_minor' => $resolved?->priceMinor,
                'effective_rule' => $resolved?->rule,
            ];
        }

        return $ladder;
    }
}
