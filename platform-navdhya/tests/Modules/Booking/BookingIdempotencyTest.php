<?php

namespace Tests\Modules\Booking;

use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use Tests\TenantIntegrationTestCase;

/**
 * Booking creation is idempotent on the `Idempotency-Key` header
 * (BACKEND-RCA 2026-10-21, finding W1).
 *
 * The header was REQUIRED but never stored: the replay check was a
 * 60-second, payload-shaped heuristic. A retry after a minute created a
 * SECOND booking, and two legitimate bookings for the same slot inside
 * the minute collapsed onto the first one.
 */
final class BookingIdempotencyTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: string, 1: string}
     */
    private function bookingFixture(): array
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();

        CatalogItem::factory()->create([
            'tenant_id' => $tenantId,
            'sku' => 'IDEMPOTENT-PUJA',
            'status' => 'ACTIVE',
            'currency' => 'INR',
            'price_minor' => 450000,
        ]);

        return [$tenantId, (string) $user->getAuthIdentifier()];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $scheduledAt = '2030-01-01T10:00:00'): array
    {
        return [
            'service_sku' => 'IDEMPOTENT-PUJA',
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 60,
        ];
    }

    public function test_the_same_key_and_payload_replays_the_same_booking(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $headers = array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-alpha']);

        $first = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), $headers)
            ->assertCreated();

        $second = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), $headers)
            ->assertOk();

        // The same booking comes back - not a second one.
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(
            1,
            Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'A replay must not create a second booking.'
        );
    }

    public function test_a_different_key_with_the_same_payload_creates_a_second_booking(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);

        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-one']))
            ->assertCreated();

        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-two']))
            ->assertCreated();

        $this->assertSame(
            2,
            Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'Two different keys are two different bookings.'
        );
    }

    public function test_the_same_key_with_a_different_payload_is_refused(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $headers = array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-beta']);

        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), $headers)
            ->assertCreated();

        // The same key, a different slot: a key reuse, not a replay.
        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload('2030-01-02T10:00:00'), $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'idempotency_key_conflict');

        $this->assertSame(
            1,
            Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'A key reuse must not create a second booking.'
        );
    }

    public function test_a_missing_key_is_refused(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);

        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), $this->tenantHeaders($tenant))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertSame(0, Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->count());
    }
}
