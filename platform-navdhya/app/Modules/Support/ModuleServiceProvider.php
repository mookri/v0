<?php

declare(strict_types=1);

namespace App\Modules\Support;

use App\Modules\Support\Console\ScanGrievanceSlaBreachesCommand;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ScanGrievanceSlaBreachesCommand::class,
            ]);
        }
    }
}

