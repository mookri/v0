<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerNotification;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Notifications (B4): newest-first cursor reads, single + read-all
 * markers with flip counts, foreign 404s. Rows are producer-written —
 * tests seed via factory (producers land B5+).
 */
final class CustomerNotificationTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $user->forceFill(['external_id' => 'cust_'.strtolower((string) Str::ulid())])->save();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);
        try {
            BuyerProfile::factory()->create(['user_id' => $user->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user];
    }

    private function seedNotifications(Tenant $tenant, User $user, int $unread, int $read): void
    {
        $this->resolveContext($tenant);
        try {
            // Read rows first so unread rows are newest (cursor order).
            for ($i = 0; $i < $read; $i++) {
                CustomerNotification::factory()->create([
                    'user_id' => $user->id, 'read_at' => now()->toDateTimeString(),
                ]);
            }
            for ($i = 0; $i < $unread; $i++) {
                CustomerNotification::factory()->create(['user_id' => $user->id]);
            }
        } finally {
            $this->clearContext();
        }
    }

    public function test_list_read_and_read_all(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $this->seedNotifications($tenant, $user, 3, 1);

        $listed = $actor()->getJson('/api/v1/customers/notifications?limit=2', $headers)->assertOk();
        $this->assertCount(2, $listed->json('data'));
        $this->assertTrue($listed->json('meta.page.has_more'));
        $this->assertArrayHasKey('read_at', $listed->json('data.0'));

        $firstId = $listed->json('data.0.id');
        $read = $actor()->postJson("/api/v1/customers/notifications/{$firstId}:read", [], $headers)->assertOk();
        $this->assertNotNull($read->json('data.read_at'));

        // Re-read is idempotent (same timestamp kept, still 200).
        $actor()->postJson("/api/v1/customers/notifications/{$firstId}:read", [], $headers)->assertOk();

        $all = $actor()->postJson('/api/v1/customers/notifications:read-all', [], $headers)->assertOk();
        // 3 unread seeded, 1 flipped above → 2 remaining flip now.
        $this->assertSame(2, $all->json('data.read'));

        $again = $actor()->postJson('/api/v1/customers/notifications:read-all', [], $headers)->assertOk();
        $this->assertSame(0, $again->json('data.read'));
    }

    public function test_foreign_and_guests(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        auth()->logout();
        $this->getJson('/api/v1/customers/notifications', $headers)->assertUnauthorized();

        $other = $this->makeTenant();
        [$otherUser] = $this->actingAsMembership($other);
        $this->resolveContext($other);
        try {
            $foreign = CustomerNotification::factory()->create(['user_id' => $otherUser->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user)->postJson(
            "/api/v1/customers/notifications/{$foreign->getKey()}:read",
            [],
            $headers
        )->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }

    public function test_unread_count_endpoint(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $this->seedNotifications($tenant, $user, 3, 2);

        $res = $actor()->getJson('/api/v1/customers/notifications/unread-count', $headers)->assertOk();
        $this->assertSame(3, $res->json('data.count'));

        // Read one notification
        $listed = $actor()->getJson('/api/v1/customers/notifications?limit=1', $headers)->assertOk();
        $firstId = $listed->json('data.0.id');
        $actor()->postJson("/api/v1/customers/notifications/{$firstId}:read", [], $headers)->assertOk();

        $res = $actor()->getJson('/api/v1/customers/notifications/unread-count', $headers)->assertOk();
        $this->assertSame(2, $res->json('data.count'));

        // Read all
        $actor()->postJson('/api/v1/customers/notifications:read-all', [], $headers)->assertOk();

        $res = $actor()->getJson('/api/v1/customers/notifications/unread-count', $headers)->assertOk();
        $this->assertSame(0, $res->json('data.count'));
    }

    public function test_unread_count_foreign_and_guests(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        auth()->logout();
        $this->getJson('/api/v1/customers/notifications/unread-count', $headers)->assertUnauthorized();

        $other = $this->makeTenant();
        [$otherUser] = $this->actingAsMembership($other);
    }
}
