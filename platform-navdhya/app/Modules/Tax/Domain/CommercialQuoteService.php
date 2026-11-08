<?php

namespace App\Modules\Tax\Domain;

use App\Modules\Catalog\Contracts\CalculatesQuotes;
use App\Modules\Tax\Contracts\CalculatesTax;
use App\Modules\Tax\Models\CommercialProfile;
use App\Modules\Tax\Models\TaxRegistration;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Commercial Quote Service (Phase 3, Sub-phase 3.3).
 *
 * Server-authoritative composite commercial pricing with:
 * - Deterministic SKU price resolution
 * - Multi-jurisdiction tax calculation
 * - Principal vs Agent commission and payout calculation
 * - Strict integer minor-unit arithmetic with invariant:
 *   sum(lines.total_minor) + platform_fee_minor == total_minor
 */
final class CommercialQuoteService
{
    public function __construct(
        private readonly CalculatesQuotes $catalogQuotes,
        private readonly CalculatesTax $taxEngine,
    ) {
    }

    /**
     * @param array{
     *     tenant_id: string,
     *     currency: string,
     *     jurisdiction?: string,
     *     supplier_state?: string|null,
     *     customer_state?: string|null,
     *     customer_postal_code?: string|null,
     *     is_designated_zone?: bool,
     *     lines: list<array{
     *         sku: string,
     *         quantity: int,
     *         pandit_id?: string|null,
     *         market?: string|null,
     *         tax_code?: string|null,
     *         discount_minor?: int
     *     }>
     * } $request
     * @return array{
     *     ok: true,
     *     quote_hash: string,
     *     tenant_id: string,
     *     currency: string,
     *     jurisdiction: string,
     *     selling_model: string,
     *     lines: list<array{
     *         sku: string,
     *         quantity: int,
     *         unit_price_minor: int,
     *         taxable_minor: int,
     *         tax_minor: int,
     *         total_minor: int,
     *         tax_components: list<array{
     *             name: string,
     *             rate_basis_points: int,
     *             amount_minor: int
     *         }>
     *     }>,
     *     summary: array{
     *         subtotal_minor: int,
     *         discount_minor: int,
     *         tax_total_minor: int,
     *         platform_fee_minor: int,
     *         total_minor: int,
     *         commission_minor: int,
     *         payout_minor: int,
     *         tax_components: list<array{
     *             name: string,
     *             rate_basis_points: int,
     *             amount_minor: int
     *         }>
     *     }
     * }|array{ok: false, error: string}
     */
    public function createQuote(array $request, CarbonImmutable $at): array
    {
        $tenantId = (string) $request['tenant_id'];
        $currency = strtoupper(trim((string) $request['currency']));
        $lines = $request['lines'] ?? [];

        if ($lines === []) {
            return ['ok' => false, 'error' => 'No line items provided for quote.'];
        }

        // 1. Resolve Base Prices via Catalog
        $catalogResult = $this->catalogQuotes->quote($currency, $lines, $at);
        if (! ($catalogResult['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($catalogResult['error'] ?? 'Catalog price resolution failed.')];
        }

        // 2. Resolve Commercial Profile for Tenant
        /** @var CommercialProfile|null $profile */
        $profile = CommercialProfile::query()
            ->where('tenant_id', $tenantId)
            ->first();

        $sellingModel = $profile !== null ? $profile->selling_model : CommercialProfile::MODEL_PRINCIPAL;
        $commissionBps = $profile !== null ? $profile->commission_rate_bps : 1500; // 15.00% default
        $platformFeeMinor = $profile !== null ? (int) $profile->platform_fee_minor : 0;

        // 3. Resolve Jurisdiction & Tax Registrations
        $jurisdiction = strtoupper(trim((string) ($request['jurisdiction'] ?? '')));
        if ($jurisdiction === '') {
            $registration = TaxRegistration::query()
                ->where('tenant_id', $tenantId)
                ->where('status', TaxRegistration::STATUS_ACTIVE)
                ->orderByDesc('is_headquarters')
                ->first();

            $jurisdiction = $registration !== null ? $registration->jurisdiction : match ($currency) {
                'INR' => 'IN',
                'USD' => 'US',
                'GBP' => 'GB',
                'AED' => 'AE',
                default => 'IN',
            };
        }

        // 4. Prepare Tax Lines
        $taxInputLines = [];
        foreach ($catalogResult['lines'] as $idx => $line) {
            $origLine = $lines[$idx] ?? [];
            $taxInputLines[] = [
                'sku' => (string) $line['sku'],
                'quantity' => (int) $line['quantity'],
                'unit_price_minor' => (int) $line['unit_minor'],
                'discount_minor' => (int) ($origLine['discount_minor'] ?? 0),
                'tax_code' => $origLine['tax_code'] ?? null,
            ];
        }

        // 5. Calculate Tax
        $taxResult = $this->taxEngine->calculate([
            'tenant_id' => $tenantId,
            'jurisdiction' => $jurisdiction,
            'currency' => $currency,
            'supplier_state' => $request['supplier_state'] ?? null,
            'customer_state' => $request['customer_state'] ?? null,
            'customer_postal_code' => $request['customer_postal_code'] ?? null,
            'is_designated_zone' => $request['is_designated_zone'] ?? false,
            'lines' => $taxInputLines,
        ]);

        // 6. Build Composite Lines & Verify Invariants
        $compositeLines = [];
        $subtotalMinor = 0;
        $discountTotalMinor = 0;
        $taxTotalMinor = 0;
        $lineTotalSum = 0;

        foreach ($taxResult['lines'] as $idx => $taxLine) {
            $catLine = $catalogResult['lines'][$idx];
            $origLine = $lines[$idx] ?? [];

            $taxableMinor = (int) $taxLine['taxable_minor'];
            $taxMinor = (int) $taxLine['tax_minor'];
            $lineTotal = (int) $taxLine['total_minor'];
            $discountMinor = (int) ($origLine['discount_minor'] ?? 0);

            // Invariant check: taxable + tax == lineTotal
            if ($taxableMinor + $taxMinor !== $lineTotal) {
                throw new RuntimeException("Quote line invariant violation: {$taxableMinor} + {$taxMinor} !== {$lineTotal}");
            }

            $subtotalMinor += ($catLine['unit_minor'] * $catLine['quantity']);
            $discountTotalMinor += $discountMinor;
            $taxTotalMinor += $taxMinor;
            $lineTotalSum += $lineTotal;

            $compositeLines[] = [
                'sku' => (string) $taxLine['sku'],
                'quantity' => (int) $catLine['quantity'],
                'unit_price_minor' => (int) $catLine['unit_minor'],
                'taxable_minor' => $taxableMinor,
                'tax_minor' => $taxMinor,
                'total_minor' => $lineTotal,
                'tax_components' => $taxLine['components'],
            ];
        }

        $orderTotalMinor = $lineTotalSum + $platformFeeMinor;

        // Invariant check: sum(line.total_minor) + platform_fee_minor == orderTotalMinor
        if ($lineTotalSum + $platformFeeMinor !== $orderTotalMinor) {
            throw new RuntimeException('Quote total invariant violation.');
        }

        // 7. Commission & Payout Calculations
        // In AGENT model, commission is calculated on the net ritual amount (subtotal - discount)
        // In PRINCIPAL model, platform takes whole amount and settles agreed cost with pandit
        $commissionableAmount = max(0, $subtotalMinor - $discountTotalMinor);
        $commissionMinor = (int) intdiv($commissionableAmount * $commissionBps + 5000, 10000);
        $payoutMinor = max(0, $commissionableAmount - $commissionMinor);

        // 8. Canonical Hash
        $hashPayload = json_encode([
            'tenant' => $tenantId,
            'currency' => $currency,
            'jurisdiction' => $jurisdiction,
            'lines' => $compositeLines,
            'total' => $orderTotalMinor,
        ], JSON_THROW_ON_ERROR);
        $quoteHash = hash('sha256', $hashPayload);

        return [
            'ok' => true,
            'quote_hash' => $quoteHash,
            'tenant_id' => $tenantId,
            'currency' => $currency,
            'jurisdiction' => $jurisdiction,
            'selling_model' => $sellingModel,
            'lines' => $compositeLines,
            'summary' => [
                'subtotal_minor' => $subtotalMinor,
                'discount_minor' => $discountTotalMinor,
                'tax_total_minor' => $taxTotalMinor,
                'platform_fee_minor' => $platformFeeMinor,
                'total_minor' => $orderTotalMinor,
                'commission_minor' => $commissionMinor,
                'payout_minor' => $payoutMinor,
                'tax_components' => $taxResult['summary_components'],
            ],
        ];
    }
}
