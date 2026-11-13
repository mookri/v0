<?php

namespace App\Modules\Jyotish;

use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Jyotish module provider (ADR-JY-01: pure domain in App\Domain\Jyotish,
 * HTTP, persistence and jobs mounted in App\Modules\Jyotish).
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        // Container bindings for Jyotish engine services land here as implementations arrive
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}

