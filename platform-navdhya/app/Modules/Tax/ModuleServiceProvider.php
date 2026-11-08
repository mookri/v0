<?php

namespace App\Modules\Tax;

use App\Modules\Tax\Contracts\CalculatesTax;
use App\Modules\Tax\Domain\TaxEngine;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Tax module provider (Phase 3, Task 3.2).
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->singleton(CalculatesTax::class, TaxEngine::class);
        $this->app->singleton(TaxEngine::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}

