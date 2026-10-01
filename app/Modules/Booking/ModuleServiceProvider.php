<?php

namespace App\Modules\Booking;

use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Booking module provider (ADR-005: the booking capability owns the whole
 * transaction lifecycle): module migrations only in B1; lifecycle domain
 * services land with the B5 reservations slice.
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
