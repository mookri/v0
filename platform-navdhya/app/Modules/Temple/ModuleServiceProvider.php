<?php

namespace App\Modules\Temple;

use App\Modules\Temple\Contracts\ListsTemples;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Temple module provider (doc 01 §2-4): port bindings + module migrations.
 * Routes are mounted file-based by the registry (routes/api.php) with the
 * fail-closed auth.any + tenant group — never loaded here, or the bare
 * duplicates would win the match and skip the group middleware.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(ListsTemples::class, TempleProfileQueries::class);
        $this->app->bind(\App\Modules\Temple\Contracts\ManagesTemples::class, TempleManagementService::class);
        $this->app->bind(\App\Modules\Temple\Contracts\ManagesTemplePujas::class, TemplePujaManagementService::class);
        $this->app->bind(\App\Modules\Temple\Contracts\ManagesTempleManagers::class, TempleManagerManagementService::class);
        $this->app->bind(\App\Modules\Temple\Contracts\ProvidesTempleDashboard::class, TempleDashboardService::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
