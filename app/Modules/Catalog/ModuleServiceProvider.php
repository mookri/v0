<?php

namespace App\Modules\Catalog;

use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Catalog module provider: loads migrations; routes mount file-based.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
