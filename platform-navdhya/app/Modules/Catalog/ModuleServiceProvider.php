<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Contracts\CalculatesQuotes;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Catalog\Contracts\ManagesVendorCatalogItems;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Catalog module provider: loads migrations, binds the catalog read port
 * (discover feed consumes it — never catalog models), the vendor
 * catalog write port (Vendor module consumes it) and the quote port
 * (checkout re-prices through it — B3); routes mount file-based.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(ListsCatalogItems::class, CatalogItemQueries::class);
        $this->app->bind(ManagesVendorCatalogItems::class, VendorCatalogManagementService::class);
        $this->app->bind(CalculatesQuotes::class, CatalogQuotes::class);
        $this->app->bind(\App\Modules\Catalog\Contracts\ResolvesServiceDurations::class, \App\Modules\Catalog\Application\CatalogServiceDefinitionManager::class);
        $this->app->bind(\App\Modules\Catalog\Contracts\ResolvesServiceKitBindings::class, \App\Modules\Catalog\Application\CatalogServiceDefinitionManager::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Catalog\Console\UnmappedCategoriesAuditCommand::class,
                \App\Modules\Catalog\Console\LintServiceKitBindingsCommand::class,
                \App\Modules\Catalog\Console\AssertSingleActivePriceCommand::class,
                \App\Modules\Catalog\Console\RefreshCatalogInsightsCommand::class,
            ]);
        }
    }
}
