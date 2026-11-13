<?php

namespace Tests\Feature\Communication;

use App\Models\User;
use App\Modules\Customers\Models\NotificationDelivery;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM10-01 (Closes C-16):
 * Devotee & staff delivery status surface, masking, and privacy scoping.
 */
final class DeliveryStatusApiTest extends TenantIntegrationTestCase
{
    public function test_unauthenticated_request_is_rejected(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $response = $this->getJson('/api/v1/customers/notifications/deliveries');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_retrieves_masked_deliveries(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $user1 = User::factory()->create([
            'phone' => '+919876543210',
            'email' => 'devotee1@example.com',
        ]);
        $this->makeMembership($tenant, $user1);

        $user2 = User::factory()->create([
            'phone' => '+919876543299',
            'email' => 'devotee2@example.com',
        ]);
        $this->makeMembership($tenant, $user2);

        // User 1 delivery (SMS)
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user1->id,
            'channel' => 'sms',
            'kind' => 'booking_confirmed',
            'status' => 'SENT',
            'recipient_address' => '+919876543210',
            'dedup_key' => 'dedup_user1_sms',
        ]);

        // User 1 delivery (Email)
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user1->id,
            'channel' => 'email',
            'kind' => 'payment_receipt',
            'status' => 'DELIVERED',
            'recipient_address' => 'devotee1@example.com',
            'dedup_key' => 'dedup_user1_email',
        ]);

        // User 2 delivery
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user2->id,
            'channel' => 'sms',
            'kind' => 'booking_confirmed',
            'status' => 'SENT',
            'recipient_address' => '+919876543299',
            'dedup_key' => 'dedup_user2_sms',
        ]);

        // Clear ambient context so HTTP middleware ResolveTenantContext can set it
        $this->clearContext();

        // Query as User 1
        $response = $this->actingAs($user1)->getJson('/api/v1/customers/notifications/deliveries', $this->tenantHeaders($tenant));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $items = $response->json('data');
        // Masking checks
        $smsItem = collect($items)->firstWhere('channel', 'sms');
        $this->assertNotNull($smsItem);
        $this->assertStringStartsWith('+9198', $smsItem['recipient_masked']);
        $this->assertStringEndsWith('210', $smsItem['recipient_masked']);
        $this->assertStringContainsString('****', $smsItem['recipient_masked']);

        $emailItem = collect($items)->firstWhere('channel', 'email');
        $this->assertNotNull($emailItem);
        $this->assertStringContainsString('***', $emailItem['recipient_masked']);
        $this->assertStringEndsWith('@example.com', $emailItem['recipient_masked']);

        $this->clearContext();

        // Filter check: channel=email
        $filteredResponse = $this->actingAs($user1)->getJson('/api/v1/customers/notifications/deliveries?channel=email', $this->tenantHeaders($tenant));
        $filteredResponse->assertOk();
        $filteredResponse->assertJsonCount(1, 'data');
        $this->assertSame('email', $filteredResponse->json('data.0.channel'));
    }
}
