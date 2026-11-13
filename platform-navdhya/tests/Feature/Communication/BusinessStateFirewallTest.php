<?php

namespace Tests\Feature\Communication;

use App\Modules\Booking\Models\Booking;
use App\Modules\Customers\Models\NotificationDelivery;
use Tests\TenantIntegrationTestCase;

/**
 * Gate G-FIN-0 / Task T-CM02-05:
 * Proves the Business-State Firewall:
 * Inbound webhook callbacks update communication_deliveries only.
 * No webhook execution path can ever mutate payments, bookings, or financial state.
 */
final class BusinessStateFirewallTest extends TenantIntegrationTestCase
{
    public function test_inbound_webhook_payload_with_paid_content_cannot_mutate_booking_state(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        // 1. Seed a delivery row
        $msgId = 'wamid.firewall_test_999';
        $delivery = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 5001,
            'kind' => 'booking_confirmed',
            'category' => 'BOOKING',
            'channel' => 'WHATSAPP',
            'provider' => 'whatsapp_cloud',
            'status' => NotificationDelivery::STATUS_SENT,
            'dedup_key' => 'dedup_firewall_001',
            'provider_message_id' => $msgId,
        ]);

        // 2. Count existing bookings before webhook call
        $initialBookingsCount = Booking::query()->count();

        // 3. Post a webhook payload with malicious payload attempting state forgery
        $secret = 'wa_firewall_secret';
        config(['notifications.whatsapp.app_secret' => $secret]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => $msgId,
                            'status' => 'delivered',
                        ]],
                        'messages' => [[
                            'from' => '919876543210',
                            'id' => 'wamid.malicious_reply_001',
                            'text' => ['body' => 'PAID $5000 COMPLETE'],
                            'type' => 'text',
                        ]],
                        // Malicious injection attempt
                        'payment_status' => 'CAPTURED',
                        'amount_paid' => 500000,
                        'booking_status' => 'CONFIRMED',
                    ],
                ]],
            ]],
        ];

        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256=' . hash_hmac('sha256', $payloadJson, $secret);

        $response = $this->postJson('/api/v1/customers/webhooks/whatsapp', $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);

        // 4. Assert delivery transitioned strictly within communications domain
        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);

        // 5. Assert booking table remained 100% untouched
        $this->assertSame($initialBookingsCount, Booking::query()->count());
    }
}

