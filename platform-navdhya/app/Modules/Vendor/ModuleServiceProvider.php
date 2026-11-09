<?php

namespace App\Modules\Vendor;

use App\Modules\Vendor\Application\StockReservationService;
use App\Modules\Vendor\Contracts\ManagesStockReservations;
use App\Modules\Vendor\Contracts\ManagesVendorInventory;
use App\Modules\Vendor\Contracts\ManagesVendorOrders;
use App\Modules\Vendor\Contracts\ManagesVendors;
use App\Modules\Vendor\Listeners\VendorLifecycleNotifier;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Outbox\OutboxHandlers;

/**
 * Vendor module provider: loads migrations and binds the vendor write
 * ports (profile, inventory, orders, stock reservations); routes mount file-based.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(ManagesVendors::class, VendorManagementService::class);
        $this->app->bind(ManagesVendorInventory::class, VendorInventoryService::class);
        $this->app->bind(ManagesVendorOrders::class, VendorOrderService::class);
        $this->app->bind(ManagesStockReservations::class, StockReservationService::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        // D0: claim vendor lifecycle outbox events.
        $handlers = $this->app->make(OutboxHandlers::class);
        foreach (VendorLifecycleNotifier::EVENT_TYPES as $eventType) {
            $handlers->register(
                $eventType,
                'vendor.lifecycle-notifier',
                function (OutboxEvent $event): void {
                    $this->app->make(VendorLifecycleNotifier::class)->handle($event);
                },
            );
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ReconcileStockCommand::class,
                Console\AccruePayoutsCommand::class,
            ]);
        }
    }
}
