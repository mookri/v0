<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\RanksServices;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Booking module provider (ADR-005: the booking capability owns the whole
 * transaction lifecycle): service-ranking + booking-read port bindings +
 * module migrations; routes mount file-based.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(RanksServices::class, ServiceRankings::class);
        $this->app->bind(ReadsBookings::class, BookingReads::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
