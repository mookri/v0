<?php

namespace Tests\Modules\Communication;

use App\Modules\Customers\Providers\Msg91SmsProvider;
use App\Modules\Customers\Providers\NotificationMessage;
use App\Modules\Customers\Providers\WhatsAppCloudProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TenantIntegrationTestCase;

/**
 * Tests closing C-02 & C-08:
 * Proves that provider adapters extract and preserve genuine external message IDs,
 * never fabricate synthetic IDs on response parse, and transmit idempotency keys.
 */
final class ProviderMessageIdPersistenceTest extends TenantIntegrationTestCase
{
    public function test_msg91_extracts_and_returns_true_external_message_id(): void
    {
        $expectedMsgId = 'msg91_req_live_99887766';

        Http::fake([
            'https://api.msg91.com/*' => Http::response([
                'type' => 'success',
                'request_id' => $expectedMsgId,
            ], 200),
        ]);

        $provider = new Msg91SmsProvider(authKey: 'valid_auth_key');

        $msg = new NotificationMessage(
            userId: 1001,
            recipient: '+919876543210',
            category: 'BOOKING',
            channel: 'SMS',
            template: 'booking_confirmed',
            variables: ['booking_id' => 'bkg_123'],
            idempotencyKey: 'idem_key_msg91_test_001'
        );

        $result = $provider->send($msg);

        $this->assertTrue($result->ok);
        $this->assertSame($expectedMsgId, $result->providerMessageId);

        // Verify idempotency header was sent
        Http::assertSent(function (Request $request) {
            return $request->hasHeader('X-Idempotency-Key', 'idem_key_msg91_test_001');
        });
    }

    public function test_msg91_missing_message_id_fails_rather_than_synthesizing_random_junk(): void
    {
        Http::fake([
            'https://api.msg91.com/*' => Http::response([
                'type' => 'success',
                // deliberately missing request_id and message
            ], 200),
        ]);

        $provider = new Msg91SmsProvider(authKey: 'valid_auth_key');

        $msg = new NotificationMessage(
            userId: 1001,
            recipient: '+919876543210',
            category: 'BOOKING',
            channel: 'SMS',
            template: 'booking_confirmed'
        );

        $result = $provider->send($msg);

        $this->assertFalse($result->ok);
        $this->assertStringContainsString('missing message/request_id', $result->error ?? '');
    }

    public function test_whatsapp_extracts_and_returns_meta_wamid_and_passes_opaque_idempotency_key(): void
    {
        $wamid = 'wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSRTI4QTMxQzA1Nzg3RTlGNTlBAA==';

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '+919876543210', 'wa_id' => '919876543210']],
                'messages' => [['id' => $wamid]],
            ], 200),
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
            template: 'booking_confirmed',
            variables: ['booking_id' => 'bkg_123'],
            idempotencyKey: 'idem_key_wa_test_002'
        );

        $result = $provider->send($msg);

        $this->assertTrue($result->ok);
        $this->assertSame($wamid, $result->providerMessageId);

        // Verify opaque callback data / idempotency key was included in payload
        Http::assertSent(function (Request $request) {
            $data = $request->data();
            return isset($data['biz_opaque_callback_data'])
                && $data['biz_opaque_callback_data'] === 'idem_key_wa_test_002';
        });
    }

    public function test_whatsapp_missing_message_id_fails_rather_than_synthesizing_random_junk(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [], // Empty messages array
            ], 200),
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
        $this->assertStringContainsString('missing messages[0].id', $result->error ?? '');
    }
}

