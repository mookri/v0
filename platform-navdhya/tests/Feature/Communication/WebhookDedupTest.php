<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Models\CommunicationWebhookEvent;
use App\Modules\Customers\Models\NotificationDelivery;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM02-03:
 * Proves that provider callbacks are deduplicated in communication_webhook_events
 * and duplicate deliveries are ignored idempotently.
 */
final class WebhookDedupTest extends TenantIntegrationTestCase
{
    public function test_retried_webhook_is_deduplicated(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $msgId = 'msg_dedup_provider_001';
        $delivery = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 3001,
            'kind' => 'reminder',
            'category' => 'REMINDER',
            'channel' => 'SMS',
            'provider' => 'msg91',
            'status' => NotificationDelivery::STATUS_SENT,
            'dedup_key' => 'dedup_receipt_callback_001',
            'provider_message_id' => $msgId,
        ]);

        $secret = 'secret_dedup_key';
        config(['notifications.msg91.webhook_secret' => $secret]);

        $payload = ['request_id' => $msgId, 'status' => 'delivered'];
        $signature = hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $secret);

        // First callback
        $res1 = $this->postJson('/api/v1/customers/webhooks/msg91', $payload, [
            'X-Msg91-Signature' => $signature,
        ]);
        $res1->assertStatus(200);

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(1, CommunicationWebhookEvent::query()->where('provider_message_id', $msgId)->count());

        // Second retried callback with identical payload
        $res2 = $this->postJson('/api/v1/customers/webhooks/msg91', $payload, [
            'X-Msg91-Signature' => $signature,
        ]);
        $res2->assertStatus(200);
        $res2->assertJson(['status' => 'acknowledged', 'deduplicated' => true]);

        // Dedup count remains exactly 1
        $this->assertSame(1, CommunicationWebhookEvent::query()->where('provider_message_id', $msgId)->count());
    }
}

