<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Platform\Identity\Contracts\TrackSeeder;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Customers module provider: TrackSeeder binding (customer track seeds a
 * buyer profile; all other tracks delegate to onboarding) and module
 * migrations. OTP rides the shared OtpChallengeManager (registered
 * platform-side with the customer store as default).
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(TrackSeeder::class, CustomersTrackSeeder::class);
        $this->app->bind(NotifiesCustomers::class, CustomerNotifier::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
