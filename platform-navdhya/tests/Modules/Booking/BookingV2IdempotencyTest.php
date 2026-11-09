<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Booking V2 Idempotency and Deduplication Tests (WP1, WP2).
 * Verifies that identical requests yield identical results and conflicting payloads
 * yield 409 Conflict Problem Details.
 */
final class BookingV2IdempotencyTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $user->forceFill(['external_id' => 'cust_' . strtolower((string) Str::ulid())])->save();
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

    private function seedCatalogItem(Tenant $tenant, string $sku): CatalogItem
    {
        $this->resolveContext($tenant);
        try {
            return CatalogItem::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'sku' => $sku,
                'status' => 'ACTIVE',
            ]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_v2_store_idempotent_replay_returns_cached_response(): void
    {
        [$tenant, $user] = $this->customer();
        $sku = 'puja_idemp_v2_' . Str::random(6);
        $this->seedCatalogItem($tenant, $sku);

        $idempotencyKey = (string) Str::uuid();
        $headers = array_merge($this->tenantHeaders($tenant), [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $payload = [
            'service_sku' => $sku,
            'scheduled_at' => CarbonImmutable::now()->addDays(5)->toIso8601String(),
            'duration_minutes' => 60,
        ];

        // First call
        $first = $this->postJson('/api/v2/booking/reservations', $payload, $headers);
        $first->assertStatus(201);
        $bookingId1 = $first->json('data.id');

        // Replay call with exact same key and payload
        $second = $this->postJson('/api/v2/booking/reservations', $payload, $headers);
        $second->assertStatus(201);
        $bookingId2 = $second->json('data.id');

        $this->assertSame($bookingId1, $bookingId2);
        $this->resolveContext($tenant);
        $count = Booking::query()->where('idempotency_key', $idempotencyKey)->count();
        $this->clearContext();
        $this->assertSame(1, $count);
    }

    public function test_v2_store_conflicting_payload_returns_409_problem_details(): void
    {
        [$tenant, $user] = $this->customer();
        $sku = 'puja_conflict_v2_' . Str::random(6);
        $this->seedCatalogItem($tenant, $sku);

        $idempotencyKey = (string) Str::uuid();
        $headers = array_merge($this->tenantHeaders($tenant), [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $payload1 = [
            'service_sku' => $sku,
            'scheduled_at' => CarbonImmutable::now()->addDays(5)->toIso8601String(),
            'duration_minutes' => 60,
        ];

        $first = $this->postJson('/api/v2/booking/reservations', $payload1, $headers);
        $first->assertStatus(201);

        // Conflicting payload with different duration
        $payload2 = [
            'service_sku' => $sku,
            'scheduled_at' => CarbonImmutable::now()->addDays(5)->toIso8601String(),
            'duration_minutes' => 120,
        ];

        $conflict = $this->postJson('/api/v2/booking/reservations', $payload2, $headers);
        $conflict->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'idempotency_conflict')
            ->assertJsonPath('title', 'Idempotency Conflict');
    }
}
