<?php

namespace App\Modules\Finance;

use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Finance module provider (Phase 5, Task 5.1).
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
