<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\RandomOtpCodes;
use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Platform\Identity\Contracts\TrackSeeder;
use App\Platform\Identity\Otp\EmailOtpDeliveryProvider;
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
        // S47/S48: the service gets the config-built provider registry
        // (firebase-<audience> rows dispatch through it; issue() picks
        // the provider per the purpose policy table) and the selected
        // email delivery channel ('null' unless opted into 'mailer').
        $this->app->singleton(
            CustomerOtpService::class,
            static fn ($app): CustomerOtpService => new CustomerOtpService(
                new RandomOtpCodes(),
                $app->make(OtpProviderRegistry::class),
                $app->make(EmailOtpDeliveryProvider::class),
            )
        );
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
