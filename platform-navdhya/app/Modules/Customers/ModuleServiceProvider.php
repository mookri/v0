<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Console\ProcessRitualRemindersCommand;
use App\Modules\Customers\Console\PurgeTelemetryCommand;
use App\Modules\Customers\Console\RefreshCustomerInsightsCommand;
use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Modules\Customers\Contracts\ResolvesCustomerParties;
use App\Modules\Customers\Services\CustomerPartyResolver;
use App\Domain\Calendar\Contracts\ComputesPanchang;
use App\Domain\Calendar\Panchang\AnchorOccurrenceResolver;
use App\Modules\Customers\Listeners\CustomerLifecycleBridge;
use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use App\Modules\Customers\Providers\FakeNotificationProvider;
use App\Modules\Customers\Providers\Msg91SmsProvider;
use App\Modules\Customers\Providers\SmtpEmailProvider;
use App\Modules\Customers\Providers\WhatsAppCloudProvider;
use App\Platform\Identity\Contracts\TrackSeeder;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Outbox\OutboxHandlers;
use Illuminate\Contracts\Foundation\Application;

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
        $this->app->bind(ResolvesCustomerParties::class, CustomerPartyResolver::class);
        $this->app->singleton(ComputesPanchang::class, AnchorOccurrenceResolver::class);

        $this->app->singleton(NotificationProvider::class, function (Application $app): NotificationProvider {
            $driver = (string) $app['config']->get('notifications.driver', 'fake');

            return match ($driver) {
                'msg91' => $app->make(Msg91SmsProvider::class),
                'whatsapp' => $app->make(WhatsAppCloudProvider::class),
                'smtp', 'email' => $app->make(SmtpEmailProvider::class),
                default => $app->make(FakeNotificationProvider::class),
            };
        });
    }

    public function bootModule(): void
    {
        $driver = (string) config('notifications.driver', 'fake');
        if ($this->app->environment('production') && $driver === 'fake') {
            throw new \RuntimeException(
                'Production environment cannot boot with NOTIFICATIONS_DRIVER=fake. Live or non-fake provider credentials must be configured.'
            );
        }

        $this->loadModuleMigrations();

        \Illuminate\Support\Facades\RateLimiter::for('webhook-msg91', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(300)->by('provider:msg91:' . ($request->header('X-Msg91-Signature') ? 'signed' : 'anon'));
        });

        \Illuminate\Support\Facades\RateLimiter::for('webhook-meta', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by('provider:meta:' . ($request->header('X-Hub-Signature-256') ? 'signed' : 'anon'));
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessRitualRemindersCommand::class,
                PurgeTelemetryCommand::class,
                RefreshCustomerInsightsCommand::class,
                \App\Domain\Delivery\Console\DeliveryReconcileCommand::class,
            ]);
        }

        if ($this->app->bound(OutboxHandlers::class)) {
            /** @var OutboxHandlers $handlers */
            $handlers = $this->app->make(OutboxHandlers::class);
            foreach (CustomerLifecycleBridge::EVENT_TYPES as $eventType) {
                $handlers->register(
                    $eventType,
                    'customers.lifecycle-bridge',
                    function (OutboxEvent $event): void {
                        $this->app->make(CustomerLifecycleBridge::class)->handle($event);
                    }
                );
            }
        }
    }
}
