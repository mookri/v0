<?php

namespace App\Modules\Customers;

use App\Platform\Identity\Contracts\TrackSeeder;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Customers module provider: TrackSeeder binding (customer track seeds a
 * buyer profile; all other tracks delegate to onboarding) and module
 * migrations. Auth OTP services land with the B2 auth slice.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(TrackSeeder::class, CustomersTrackSeeder::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
