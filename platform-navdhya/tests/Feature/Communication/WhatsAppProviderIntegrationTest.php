<?php

namespace Tests\Feature\Communication;

use App\Modules\Customers\Providers\NotificationMessage;
use App\Modules\Customers\Providers\WhatsAppCloudProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM04-01:
 * Validates Meta Cloud API v21.0 payload serialization and compatibility.
 */
final class WhatsAppProviderIntegrationTest extends TenantIntegrationTestCase
{
    public function test_whatsapp_cloud_provider_serializes_meta_v21_payload_cleanly(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/123456789/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '+919876543210', 'wa_id' => '919876543210']],
                'messages' => [['id' => 'wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSRTI4QTMxQzA1Nzg3RTlGNTlB']],
            ], 200),
        ]);

        $provider = new WhatsAppCloudProvider(
            phoneNumberId: '123456789',
            accessToken: 'EA_test_token',
            apiVersion: 'v21.0'
        );

        $msg = new NotificationMessage(
            userId: 7001,
            recipient: '+919876543210',
            category: 'BOOKING',
            channel: 'WHATSAPP',
            template: 'booking_confirmed_v21',
            variables: ['Puja Name', '15 Nov 2026'],
            idempotencyKey: 'idem_meta_v21_001'
        );

        $result = $provider->send($msg);

        $this->assertTrue($result->ok);
        $this->assertSame('wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSRTI4QTMxQzA1Nzg3RTlGNTlB', $result->providerMessageId);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $data['messaging_product'] === 'whatsapp'
                && $data['recipient_type'] === 'individual'
                && $data['to'] === '+919876543210'
                && $data['type'] === 'template'
                && $data['template']['name'] === 'booking_confirmed_v21'
                && $data['template']['language']['code'] === 'en_US'
                && count($data['template']['components'][0]['parameters']) === 2
                && $data['template']['components'][0]['parameters'][0]['text'] === 'Puja Name'
                && $data['biz_opaque_callback_data'] === 'idem_meta_v21_001';
        });
    }
}

