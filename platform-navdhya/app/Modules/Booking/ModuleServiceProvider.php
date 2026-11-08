<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\RanksServices;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Booking\Contracts\TransitionsBookingFulfillment;
use App\Modules\Booking\Contracts\TransitionsBookingPayment;
use App\Modules\Booking\Listeners\BookingLifecycleNotifier;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Outbox\OutboxHandlers;

/**
 * Booking module provider (ADR-005: the booking capability owns the whole
 * transaction lifecycle): service-ranking + booking-read + outcome port
 * bindings (B3 payment webhook, B4 pandit fulfillment — both consumed by
 * other modules) + module migrations; routes mount file-based.
 *
 * N2: registers the booking lifecycle outbox handlers (first production
 * handlers) — the drain replays created/cancelled/rescheduled/transitioned
 * facts into customer notifications through the customers port.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(RanksServices::class, ServiceRankings::class);
        $this->app->bind(ReadsBookings::class, BookingReads::class);
        $this->app->bind(TransitionsBookingPayment::class, BookingPaymentTransitions::class);
        $this->app->bind(TransitionsBookingFulfillment::class, BookingFulfillmentTransitions::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        // N2: claim the booking lifecycle events. The listener resolves
        // per dispatch (the customers port binding is read at drain time,
        // never at boot — no cross-module boot-order coupling).
        $handlers = $this->app->make(OutboxHandlers::class);
        foreach (BookingLifecycleNotifier::EVENT_TYPES as $eventType) {
            $handlers->register(
                $eventType,
                'booking.lifecycle-notifier',
                function (OutboxEvent $event): void {
                    $this->app->make(BookingLifecycleNotifier::class)->handle($event);
                },
            );
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ReapExpiredHoldsCommand::class,
            ]);
        }
    }
}
