<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\RandomOtpCodes;
use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Platform\Identity\Contracts\TrackSeeder;
use App\Platform\Identity\Otp\OtpProviderRegistry;
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
        // S47: the service gets the config-built provider registry so
        // firebase-<audience> rows can dispatch. Routing (which audience
        // a challenge takes at issue()) is S48 — issue() still picks
        // local, verify() honors any row's provider either way.
        $this->app->singleton(
            CustomerOtpService::class,
            static fn ($app): CustomerOtpService => new CustomerOtpService(
                new RandomOtpCodes(),
                $app->make(OtpProviderRegistry::class),
            )
        );
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
