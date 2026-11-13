<?php

namespace Tests\Modules\Communication;

use App\Modules\Customers\Providers\Msg91SmsProvider;
use App\Modules\Customers\Providers\NotificationMessage;
use App\Modules\Customers\Providers\WhatsAppCloudProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TenantIntegrationTestCase;

/**
 * Tests closing C-08:
 * Proves that HTTP calls enforce timeouts, distinguish connect failures from read timeouts,
 * and flag ambiguous 504 responses for reconciliation instead of blind resends.
 */
final class ProviderTimeoutTest extends TenantIntegrationTestCase
{
    public function test_msg91_504_gateway_timeout_is_marked_ambiguous(): void
    {
        Http::fake([
            'https://api.msg91.com/*' => Http::response('Gateway Timeout', 504),
        ]);

        $provider = new Msg91SmsProvider(authKey: 'live_test_auth_key_12345');

        $msg = new NotificationMessage(
            userId: 1001,
            recipient: '+919876543210',
            category: 'BOOKING',
            channel: 'SMS',
            template: 'booking_confirmed'
        );

        $result = $provider->send($msg);

        $this->assertFalse($result->ok);
        $this->assertTrue($result->isAmbiguous);
        $this->assertStringContainsString('504 Gateway Timeout', $result->error ?? '');
    }

    public function test_whatsapp_connection_timeout_is_marked_ambiguous(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 5001 milliseconds');
            },
        ]);

        $provider = new WhatsAppCloudProvider(
            phoneNumberId: '10987654321',
            accessToken: 'EA_test_access_token_123'
        );

        $msg = new NotificationMessage(
            userId: 1001,
            recipient: '+919876543210',
            category: 'BOOKING',
            channel: 'WHATSAPP',
            template: 'booking_confirmed'
        );

        $result = $provider->send($msg);

        $this->assertFalse($result->ok);
        $this->assertTrue($result->isAmbiguous);
        $this->assertStringContainsString('timeout', strtolower($result->error ?? ''));
    }

    public function test_whatsapp_immediate_connect_failure_is_not_ambiguous(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => function () {
                throw new ConnectionException('cURL error 7: Failed to connect to host');
            },
        ]);

        $provider = new WhatsAppCloudProvider(
            phoneNumberId: '10987654321',
            accessToken: 'EA_test_access_token_123'
        );

        $msg = new NotificationMessage(
            userId: 1001,
            recipient: '+919876543210',
            category: 'BOOKING',
            channel: 'WHATSAPP',
            template: 'booking_confirmed'
        );

        $result = $provider->send($msg);

        $this->assertFalse($result->ok);
        $this->assertFalse($result->isAmbiguous);
    }
}

