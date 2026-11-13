<?php

namespace Tests\Feature\Communication;

use App\Modules\Customers\Models\NotificationDelivery;
use Illuminate\Support\Facades\Log;
use Tests\TenantIntegrationTestCase;

/**
 * Tests closing C-06 (Task T-CM02-02):
 * Proves that webhook receipts cannot leak across tenants or mutate another tenant's delivery rows.
 */
final class TenantScopedWebhookTest extends TenantIntegrationTestCase
{
    public function test_webhook_receipt_for_tenant_a_cannot_mutate_delivery_of_tenant_b(): void
    {
        // 1. Create Tenant A and Tenant B
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        $tenantAId = (string) $tenantA->getKey();
        $tenantBId = (string) $tenantB->getKey();

        // 2. Seed a delivery belonging to Tenant B
        $this->resolveContext($tenantB);
        $bMsgId = 'wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSRTI4QTMxQzA1Nzg3RTlGNTlCB';
        $deliveryB = NotificationDelivery::query()->create([
            'tenant_id' => $tenantBId,
            'user_id' => 2002,
            'kind' => 'booking_confirmed',
            'category' => 'BOOKING',
            'channel' => 'WHATSAPP',
            'provider' => 'whatsapp_cloud',
            'status' => NotificationDelivery::STATUS_SENT,
            'dedup_key' => 'dedup_tenant_b_delivery_01',
            'provider_message_id' => $bMsgId,
        ]);

        // 3. Switch active context to Tenant A
        $this->resolveContext($tenantA);

        $secret = 'test_wa_secret_key_123';
        config(['notifications.whatsapp.app_secret' => $secret]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => $bMsgId,
                            'status' => 'delivered',
                        ]],
                    ],
                ]],
            ]],
        ];

        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256=' . hash_hmac('sha256', $payloadJson, $secret);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) use ($bMsgId) {
                return str_contains($message, 'Unmatched webhook receipt')
                    && ($context['telemetry_metric'] ?? null) === 'unmatched_receipt_count';
            });

        // Post webhook to Tenant A
        $response = $this->postJson('/api/v1/customers/webhooks/whatsapp', $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);

        // 4. Verify Tenant B's delivery row remained untouched (still SENT, not delivered)
        $this->resolveContext($tenantB);
        $deliveryB->refresh();
        $this->assertSame(NotificationDelivery::STATUS_SENT, $deliveryB->status);
        $this->assertNull($deliveryB->delivered_at);
    }
}

