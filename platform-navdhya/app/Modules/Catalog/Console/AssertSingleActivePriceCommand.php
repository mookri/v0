<?php

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Models\CatalogPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class AssertSingleActivePriceCommand extends Command
{
    protected $signature = 'catalog:assert-single-active-price {--tenant= : Tenant ID filter}';

    protected $description = 'Asserts that at most one active price exists per SKU/market/currency/pandit/tier tuple';

    public function handle(): int
    {
        $this->info('Asserting Single-Active Price Integrity...');

        $query = CatalogPrice::query()
            ->select([
                'tenant_id',
                'sku',
                'market',
                'currency',
                'pandit_id',
                'package_tier_code',
                'qualification_tier_code',
                DB::raw('COUNT(*) as active_count'),
            ])
            ->where(function ($q): void {
                $q->where('is_current', true)
                  ->orWhereNull('effective_to');
            })
            ->groupBy([
                'tenant_id',
                'sku',
                'market',
                'currency',
                'pandit_id',
                'package_tier_code',
                'qualification_tier_code',
            ])
            ->having('active_count', '>', 1);

        if ($tenant = $this->option('tenant')) {
            $query->where('tenant_id', $tenant);
        }

        $duplicates = $query->get();

        if ($duplicates->isEmpty()) {
            $this->info('Price integrity verified: Zero duplicate active prices detected across all pricing axes.');
            return 0;
        }

        $this->error("Integrity check failed: {$duplicates->count()} duplicate active price tuples found!");

        $rows = [];
        foreach ($duplicates as $dup) {
            $rows[] = [
                'Tenant' => $dup->tenant_id,
                'SKU' => $dup->sku,
                'Market' => $dup->market,
                'Currency' => $dup->currency,
                'Pandit ID' => $dup->pandit_id ?? 'NULL',
                'Package Tier' => $dup->package_tier_code ?? 'NULL',
                'Qual Tier' => $dup->qualification_tier_code ?? 'NULL',
                'Active Count' => $dup->active_count,
            ];
        }

        $this->table(['Tenant', 'SKU', 'Market', 'Currency', 'Pandit ID', 'Package Tier', 'Qual Tier', 'Active Count'], $rows);

        return 1;
    }
}

