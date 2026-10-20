<?php

namespace App\Modules\Vendor;

use App\Modules\Vendor\Contracts\ManagesVendorInventory;
use App\Modules\Vendor\Contracts\ManagesVendorOrders;
use App\Modules\Vendor\Contracts\ManagesVendors;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Vendor module provider: loads migrations and binds the vendor write
 * ports (profile, inventory, orders); routes mount file-based. Product
 * ports stay in the Catalog module (P12a) and are injected directly by
 * the product controller.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(ManagesVendors::class, VendorManagementService::class);
        $this->app->bind(ManagesVendorInventory::class, VendorInventoryService::class);
        $this->app->bind(ManagesVendorOrders::class, VendorOrderService::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
