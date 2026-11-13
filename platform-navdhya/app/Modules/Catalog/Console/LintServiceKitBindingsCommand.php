<?php

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogServiceKitBinding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class LintServiceKitBindingsCommand extends Command
{
    protected $signature = 'catalog:lint-bindings {--tenant= : Filter by tenant ID}';

    protected $description = 'Lints service-kit bindings asserting kit existence, active status, kind, and BOM readiness';

    public function handle(): int
    {
        $this->info('Linting Catalog Service-Kit Bindings...');

        $query = CatalogServiceKitBinding::query();
        if ($tenant = $this->option('tenant')) {
            $query->where('tenant_id', $tenant);
        }

        $bindings = $query->get();
        if ($bindings->isEmpty()) {
            $this->info('No service-kit bindings found.');
            return 0;
        }

        $errors = 0;
        $warnings = 0;
        $rows = [];

        $hasVendorComponentsTable = Schema::connection('tenant_shared')->hasTable('vendor_kit_components');

        foreach ($bindings as $binding) {
            $service = CatalogItem::find($binding->service_item_id);
            $kit = CatalogItem::find($binding->kit_item_id);

            $status = 'OK';
            $issue = '';

            if ($service === null) {
                $status = 'ERROR';
                $issue = "Orphan service_item_id [{$binding->service_item_id}]";
                $errors++;
            } elseif ($kit === null) {
                $status = 'ERROR';
                $issue = "Orphan kit_item_id [{$binding->kit_item_id}]";
                $errors++;
            } else {
                if ($kit->item_kind !== CatalogItem::KIND_KIT) {
                    $status = 'ERROR';
                    $issue = "Kit item [{$kit->sku}] has item_kind [{$kit->item_kind}], expected [KIT]";
                    $errors++;
                }

                if ($kit->status !== CatalogItem::STATUS_ACTIVE) {
                    $status = 'WARNING';
                    $issue = "Kit item [{$kit->sku}] is not ACTIVE (current: {$kit->status})";
                    $warnings++;
                }

                if ($service->tenant_id !== null && $kit->tenant_id !== null && $service->tenant_id !== $kit->tenant_id) {
                    $status = 'ERROR';
                    $issue = "Tenant mismatch: service [{$service->tenant_id}] vs kit [{$kit->tenant_id}]";
                    $errors++;
                }

                if ($hasVendorComponentsTable) {
                    $componentCount = \Illuminate\Support\Facades\DB::connection('tenant_shared')
                        ->table('vendor_kit_components')
                        ->where('kit_sku', $kit->sku)
                        ->count();

                    if ($componentCount === 0 && $issue === '') {
                        $status = 'WARNING';
                        $issue = "Kit [{$kit->sku}] has no BOM components in vendor_kit_components";
                        $warnings++;
                    }
                }
            }

            $rows[] = [
                'ID' => (string) $binding->getKey(),
                'Service SKU' => $service?->sku ?? 'N/A',
                'Kit SKU' => $kit?->sku ?? 'N/A',
                'Obligation' => $binding->obligation,
                'Qty' => $binding->quantity_per_party,
                'Status' => $status,
                'Details' => $issue ?: 'Verified',
            ];
        }

        $this->table(['ID', 'Service SKU', 'Kit SKU', 'Obligation', 'Qty', 'Status', 'Details'], $rows);

        if ($errors > 0) {
            $this->error("Lint failed with {$errors} error(s) and {$warnings} warning(s).");
            return 1;
        }

        $this->info("All service-kit bindings verified clean ({$warnings} warnings).");
        return 0;
    }
}

