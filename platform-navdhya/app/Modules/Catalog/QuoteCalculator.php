<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Domain\Pricing\TieredPriceResolver;
use App\Modules\Catalog\Models\CatalogItem;
use Carbon\CarbonImmutable;

/**
 * Deterministic quote calculation with 2-axis tiered pricing (F4):
 * each line resolves its effective price at the quote instant via TieredPriceResolver.
 * Unknown SKUs and currency mismatches fail the whole quote (never partial totals).
 * Integer minor-unit arithmetic only — no floats anywhere.
 */
final class QuoteCalculator
{
    private readonly TieredPriceResolver $resolver;

    public function __construct(?TieredPriceResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new TieredPriceResolver();
    }

    /**
     * @param list<array{
     *     sku: string,
     *     quantity: int,
     *     pandit_id?: string|null,
     *     package_tier?: string|null,
     *     qualification_tier?: string|null,
     *     market?: string|null
     * }> $lines
     * @return array{
     *     lines: list<array{
     *         sku: string,
     *         quantity: int,
     *         pandit_id?: string|null,
     *         package_tier?: string|null,
     *         qualification_tier?: string|null,
     *         market: string,
     *         effective_rule: string,
     *         unit_minor: int,
     *         line_minor: int
     *     }>,
     *     total_minor: int,
     *     currency: string,
     *     tier_ladder?: list<array<string, mixed>>
     * }
     */
    public function quote(string $currency, array $lines, CarbonImmutable $at, string $defaultMarket = 'in'): array
    {
        $computed = [];
        $total = 0;

        foreach ($lines as $line) {
            $sku = (string) ($line['sku'] ?? '');
            $quantity = (int) ($line['quantity'] ?? 0);
            $panditId = isset($line['pandit_id']) && $line['pandit_id'] !== null
                ? (string) $line['pandit_id']
                : null;
            $packageTier = isset($line['package_tier']) && $line['package_tier'] !== null
                ? (string) $line['package_tier']
                : null;
            $qualificationTier = isset($line['qualification_tier']) && $line['qualification_tier'] !== null
                ? (string) $line['qualification_tier']
                : null;
            $market = (string) ($line['market'] ?? $defaultMarket);

            if ($sku === '' || $quantity <= 0) {
                throw new QuoteFailedException('invalid-line');
            }

            $item = CatalogItem::query()->where('sku', $sku)->first();
            if ($item === null || $item->status !== CatalogItem::STATUS_ACTIVE) {
                throw new QuoteFailedException("unknown-or-inactive-sku:{$sku}");
            }

            $resolved = $this->resolver->resolve(
                sku: $sku,
                currency: $currency,
                market: $market,
                at: $at,
                panditId: $panditId,
                packageTier: $packageTier,
                qualificationTier: $qualificationTier
            );

            if ($resolved === null) {
                throw new QuoteFailedException("no-price:{$sku}" . ($panditId !== null ? " (pandit_id:{$panditId})" : ''));
            }

            $lineMinor = $resolved->priceMinor * $quantity;
            $total += $lineMinor;

            $computed[] = [
                'sku' => $sku,
                'quantity' => $quantity,
                'pandit_id' => $panditId,
                'package_tier' => $packageTier,
                'qualification_tier' => $qualificationTier,
                'market' => $market,
                'effective_rule' => $resolved->rule,
                'unit_minor' => $resolved->priceMinor,
                'line_minor' => $lineMinor,
            ];
        }

        $result = [
            'lines' => $computed,
            'total_minor' => $total,
            'currency' => $currency,
        ];

        if (count($lines) === 1) {
            $singleSku = (string) $lines[0]['sku'];
            $singleMarket = (string) ($lines[0]['market'] ?? $defaultMarket);
            $singleQual = isset($lines[0]['qualification_tier']) ? (string) $lines[0]['qualification_tier'] : null;

            $result['tier_ladder'] = $this->resolver->resolveLadder(
                sku: $singleSku,
                currency: $currency,
                market: $singleMarket,
                at: $at,
                qualificationTier: $singleQual
            );
        }

        return $result;
    }
}
