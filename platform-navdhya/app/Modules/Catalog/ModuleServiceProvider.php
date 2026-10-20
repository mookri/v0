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
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
