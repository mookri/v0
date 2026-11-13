<?php

declare(strict_types=1);

namespace App\Modules\Incident;

use App\Modules\Incident\Application\IncidentLifecycleService;
use App\Modules\Incident\Contracts\ManagesIncidents;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(ManagesIncidents::class, IncidentLifecycleService::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}

