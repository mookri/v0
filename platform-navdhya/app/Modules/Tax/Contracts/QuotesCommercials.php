<?php

namespace App\Modules\Tax\Contracts;

use Carbon\CarbonImmutable;

/**
 * Port for server-authoritative composite commercial quote calculations (WP3).
 * Computes exact minor-unit pricing, multi-jurisdiction tax, commissions, and platform fees.
 */
interface QuotesCommercials
{
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
     * @param CarbonImmutable $at
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
    public function createQuote(array $request, CarbonImmutable $at): array;
}
