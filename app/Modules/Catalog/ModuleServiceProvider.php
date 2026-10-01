<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Catalog module provider: loads migrations, binds the catalog read port
 * (discover feed consumes it — never catalog models); routes mount
 * file-based.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(ListsCatalogItems::class, CatalogItemQueries::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
