<?php

namespace App\Modules\Tax\Domain\Jurisdictions;

interface JurisdictionCalculatorInterface
{
    /**
     * @param array{
     *     tenant_id: string,
     *     jurisdiction: string,
     *     currency: string,
     *     supplier_state?: string|null,
     *     customer_state?: string|null,
     *     customer_postal_code?: string|null,
     *     is_designated_zone?: bool,
     *     lines: list<array{
     *         sku: string,
     *         tax_code?: string|null,
     *         category?: string|null,
     *         quantity: int,
     *         unit_price_minor: int,
     *         discount_minor?: int
     *     }>
     * } $request
     * @return array{
     *     jurisdiction: string,
     *     currency: string,
     *     taxable_amount_minor: int,
     *     tax_amount_minor: int,
     *     total_amount_minor: int,
     *     lines: list<array{
     *         sku: string,
     *         taxable_minor: int,
     *         tax_minor: int,
     *         total_minor: int,
     *         rate_basis_points: int,
     *         components: list<array{
     *             name: string,
     *             rate_basis_points: int,
     *             amount_minor: int
     *         }>
     *     }>,
     *     summary_components: list<array{
     *         name: string,
     *         rate_basis_points: int,
     *         amount_minor: int
     *     }>
     * }
     */
    public function calculate(array $request): array;
}
