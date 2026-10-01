<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\RanksServices;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Booking module provider (ADR-005: the booking capability owns the whole
 * transaction lifecycle): service-ranking port binding + module
 * migrations; routes mount file-based.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(RanksServices::class, ServiceRankings::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
