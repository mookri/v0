<?php

namespace Tests\Feature\Communication;

use App\Modules\Customers\Models\NotificationDelivery;
use Tests\TenantIntegrationTestCase;

/**
 * Tests closing C-07 (Task T-CM02-01):
 * Proves that incoming webhook endpoints enforce fail-closed cryptographic signature verification.
 * Missing, invalid, forged, or unconfigured secrets reject callbacks immediately.
 */
final class WebhookSignatureVerificationTest extends TenantIntegrationTestCase
{
    public function test_msg91_rejects_missing_signature(): void
    {
        config(['notifications.msg91.webhook_secret' => 'valid_secret_key']);

        $response = $this->postJson('/api/v1/customers/webhooks/msg91', [
            'request_id' => 'req_test_001',
            'status' => 'delivered',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'invalid_signature']);
    }

    public function test_msg91_rejects_forged_signature(): void
    {
        config(['notifications.msg91.webhook_secret' => 'valid_secret_key']);

        $response = $this->postJson('/api/v1/customers/webhooks/msg91', [
            'request_id' => 'req_test_001',
            'status' => 'delivered',
        ], [
            'X-Msg91-Signature' => 'forged_fake_signature_hash_0000000000',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'invalid_signature']);
    }

    public function test_msg91_accepts_valid_signature(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $secret = 'valid_secret_key_123';
        config(['notifications.msg91.webhook_secret' => $secret]);

        $payload = ['request_id' => 'req_valid_001', 'status' => 'delivered'];
        $signature = hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $secret);

        $response = $this->postJson('/api/v1/customers/webhooks/msg91', $payload, [
            'X-Msg91-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'acknowledged', 'message_id' => 'req_valid_001']);
    }

    public function test_whatsapp_rejects_missing_signature(): void
    {
        config(['notifications.whatsapp.app_secret' => 'valid_wa_secret']);

        $response = $this->postJson('/api/v1/customers/webhooks/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.12345',
                            'status' => 'delivered',
                        ]],
                    ],
                ]],
            ]],
        ]);

        $response->assertStatus(401);
    }

    public function test_whatsapp_rejects_forged_signature(): void
    {
        config(['notifications.whatsapp.app_secret' => 'valid_wa_secret']);

        $response = $this->postJson('/api/v1/customers/webhooks/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.12345',
                            'status' => 'delivered',
                        ]],
                    ],
                ]],
            ]],
        ], [
            'X-Hub-Signature-256' => 'sha256=bad_forged_hash',
        ]);

        $response->assertStatus(401);
    }
}

