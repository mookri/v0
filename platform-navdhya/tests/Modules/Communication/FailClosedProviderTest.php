<?php

namespace Tests\Modules\Communication;

use App\Domain\Delivery\Exceptions\ProviderNotConfiguredException;
use App\Modules\Customers\Providers\Msg91SmsProvider;
use App\Modules\Customers\Providers\NotificationMessage;
use App\Modules\Customers\Providers\WhatsAppCloudProvider;
use Tests\TenantIntegrationTestCase;

/**
 * Tests closing C-01 and C-03:
 * Proves that unconfigured providers fail closed loudly,
 * never fabricate simulated success, and the boot guard rejects fake driver in production.
 */
final class FailClosedProviderTest extends TenantIntegrationTestCase
{
    public function test_msg91_unconfigured_auth_key_throws_loud_exception(): void
    {
        $provider = new Msg91SmsProvider(authKey: '');

        $msg = new NotificationMessage(
            userId: 1001,
            recipient: '+919876543210',
            category: 'BOOKING',
            channel: 'SMS',
            template: 'booking_confirmed',
            variables: ['booking_id' => 'bkg_123']
        );

        $this->expectException(ProviderNotConfiguredException::class);
        $this->expectExceptionMessage('msg91.auth_key');

        $provider->send($msg);
    }

    public function test_whatsapp_unconfigured_credentials_throw_loud_exception(): void
    {
        $provider = new WhatsAppCloudProvider(phoneNumberId: '', accessToken: '');

        $msg = new NotificationMessage(
            userId: 1001,
            recipient: '+919876543210',
            category: 'BOOKING',
            channel: 'WHATSAPP',
            template: 'booking_confirmed',
            variables: ['booking_id' => 'bkg_123']
        );

        $this->expectException(ProviderNotConfiguredException::class);
        $this->expectExceptionMessage('whatsapp.access_token_or_phone_number_id');

        $provider->send($msg);
    }

    public function test_boot_guard_rejects_fake_driver_in_production(): void
    {
        // Mock application environment as production
        app()['env'] = 'production';
        config(['notifications.driver' => 'fake']);

        $serviceProvider = new \App\Modules\Customers\ModuleServiceProvider(app());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Production environment cannot boot with NOTIFICATIONS_DRIVER=fake');

        $serviceProvider->bootModule();
    }
}

