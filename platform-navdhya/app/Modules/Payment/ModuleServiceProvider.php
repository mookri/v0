<?php

namespace App\Modules\Payment;

use App\Modules\Payment\Contracts\CreatesPaymentIntents;
use App\Modules\Payment\Gateways\FakeGateway;
use App\Modules\Payment\Gateways\PaymentGateway;
use App\Modules\Payment\Gateways\RazorpayGateway;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;
use Illuminate\Contracts\Foundation\Application;

/**
 * Payment module provider (B3 checkout): binds the intent-creation port
 * (the booking module's :confirm consumes it) and the provider gateway
 * (razorpay in production, fake for tests/E2E — the webhook verifies
 * real HMAC signatures either way); module migrations; routes mount
 * file-based.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(CreatesPaymentIntents::class, PaymentIntents::class);

        $this->app->bind(PaymentGateway::class, function (Application $app): PaymentGateway {
            if ($app['config']->get('payments.driver', 'razorpay') === 'fake') {
                return new FakeGateway();
            }

            $keyId = $app['config']->get('payments.razorpay.key_id');
            $keySecret = $app['config']->get('payments.razorpay.key_secret');

            return new RazorpayGateway(
                is_string($keyId) ? $keyId : null,
                is_string($keySecret) ? $keySecret : null,
                (string) $app['config']->get('payments.razorpay.api_base', 'https://api.razorpay.com/v1'),
            );
        });
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
