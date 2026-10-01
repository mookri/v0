<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\RandomOtpCodes;
use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Platform\Identity\Contracts\TrackSeeder;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Customers module provider: TrackSeeder binding (customer track seeds a
 * buyer profile; all other tracks delegate to onboarding) and module
 * migrations. OTP codes are random in production; tests rebind the
 * service with SeededOtpCodes.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(TrackSeeder::class, CustomersTrackSeeder::class);
        $this->app->bind(NotifiesCustomers::class, CustomerNotifier::class);
        $this->app->singleton(
            CustomerOtpService::class,
            static fn (): CustomerOtpService => new CustomerOtpService(new RandomOtpCodes())
        );
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
