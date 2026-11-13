<?php

namespace App\Modules\Calendar;

use App\Domain\Calendar\Console\CalendarActivateCommand;
use App\Domain\Calendar\Console\CalendarBuildCommand;
use App\Domain\Calendar\Console\CalendarReplayCommand;
use App\Domain\Calendar\Console\CalendarValidateCommand;
use App\Domain\Calendar\Console\CalendarVerifyOracleCommand;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Calendar module provider (Fixes D-9: gives Calendar its own dedicated service provider).
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CalendarBuildCommand::class,
                CalendarValidateCommand::class,
                CalendarActivateCommand::class,
                CalendarReplayCommand::class,
                CalendarVerifyOracleCommand::class,
            ]);
        }
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}

