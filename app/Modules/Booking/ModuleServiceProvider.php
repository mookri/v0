<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\RanksServices;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Booking\Contracts\TransitionsBookingPayment;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Booking module provider (ADR-005: the booking capability owns the whole
 * transaction lifecycle): service-ranking + booking-read + payment-outcome
 * port bindings (B3 — the payment webhook consumes the latter) + module
 * migrations; routes mount file-based.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(RanksServices::class, ServiceRankings::class);
        $this->app->bind(ReadsBookings::class, BookingReads::class);
        $this->app->bind(TransitionsBookingPayment::class, BookingPaymentTransitions::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
