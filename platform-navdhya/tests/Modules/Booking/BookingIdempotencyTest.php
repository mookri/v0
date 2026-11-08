<?php

namespace Tests\Modules\Booking;

use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_the_same_key_with_a_changed_inline_address_is_refused(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $headers = array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-address']);
        $payload = $this->payload();
        $payload['address_snapshot'] = [
            'line1' => '12 Temple Road',
            'locality' => ['city' => 'Pune', 'postal_code' => '411001'],
        ];

        $first = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $payload, $headers)
            ->assertCreated();

        $changed = $payload;
        $changed['address_snapshot']['locality']['postal_code'] = '411002';

        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $changed, $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'idempotency_key_conflict');

        $booking = Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->sole();
        $this->assertSame($first->json('data.id'), (string) $booking->getKey());
        $this->assertSame($payload['address_snapshot'], $booking->address_snapshot);
    }

    public function test_recursively_reordered_inline_address_keys_replay_the_same_booking(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $headers = array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-address-order']);
        $payload = $this->payload();
        $payload['address_snapshot'] = [
            'line1' => '12 Temple Road',
            'locality' => ['city' => 'Pune', 'postal_code' => '411001'],
            'contacts' => [
                ['name' => 'Test Customer', 'phone' => '+919876543210'],
                ['name' => 'Test Host', 'phone' => '+919876543211'],
            ],
        ];

        $first = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $payload, $headers)
            ->assertCreated();

        $reordered = $payload;
        $reordered['address_snapshot'] = [
            'contacts' => [
                ['phone' => '+919876543210', 'name' => 'Test Customer'],
                ['phone' => '+919876543211', 'name' => 'Test Host'],
            ],
            'locality' => ['postal_code' => '411001', 'city' => 'Pune'],
            'line1' => '12 Temple Road',
        ];

        $second = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $reordered, $headers)
            ->assertOk();

        $booking = Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->sole();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('data.id'), (string) $booking->getKey());
        $this->assertEquals($payload['address_snapshot'], $booking->address_snapshot);
        $this->assertSame(1, (int) $booking->version);
    }

    #[DataProvider('equivalentTimestamps')]
    public function test_equivalent_timestamps_replay_the_same_booking(string $scheduledAt): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $headers = array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-timestamp']);

        $first = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload('2030-01-01T10:00:00Z'), $headers)
            ->assertCreated();

        $second = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload($scheduledAt), $headers)
            ->assertOk();

        $booking = Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->sole();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('data.id'), (string) $booking->getKey());
        $this->assertSame($first->json('data.scheduled_at'), $second->json('data.scheduled_at'));
        $this->assertSame(1, (int) $booking->version);
    }

    public static function equivalentTimestamps(): array
    {
        return [
            'explicit UTC offset' => ['2030-01-01T10:00:00+00:00'],
            'positive timezone offset' => ['2030-01-01T15:30:00+05:30'],
            'negative timezone offset' => ['2030-01-01T05:00:00-05:00'],
        ];
    }

    public function test_an_omitted_default_duration_replays_with_explicit_sixty_minutes(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $headers = array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-default-duration']);
        $payload = $this->payload();
        unset($payload['duration_minutes']);

        $first = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $payload, $headers)
            ->assertCreated();

        $second = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), $headers)
            ->assertOk();

        $booking = Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->sole();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('data.id'), (string) $booking->getKey());
        $this->assertSame(60, (int) $booking->duration_minutes);
        $this->assertSame(1, (int) $booking->version);
    }

    public function test_a_unique_key_race_with_a_different_payload_is_refused(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $seed = $this->actingAs($user)->postJson(
            '/api/v1/booking/reservations',
            $this->payload('2030-01-02T10:00:00'),
            array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-race-seed']),
        )->assertCreated();

        $winner = Booking::withoutGlobalScopes()->findOrFail($seed->json('data.id'))->replicate();
        $winner->idempotency_key = 'key-race';
        $armed = true;
        $loserAttempted = false;
        $dispatcher = Booking::getEventDispatcher();
        Booking::setEventDispatcher(clone $dispatcher);
        Booking::creating(function (Booking $booking) use ($winner, &$loserAttempted): void {
            if ($booking !== $winner && $booking->idempotency_key === 'key-race') {
                $loserAttempted = true;
            }
        });

        $winner->getConnection()->beforeStartingTransaction(function () use ($winner, &$armed): void {
            if ($armed) {
                $armed = false;
                $winner->save();
            }
        });

        try {
            $response = $this->actingAs($user)->postJson(
                '/api/v1/booking/reservations',
                $this->payload(),
                array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'key-race']),
            );
        } finally {
            $armed = false;
            Booking::setEventDispatcher($dispatcher);
        }

        $this->assertTrue($loserAttempted, 'The request must reach the insert, not the initial replay check.');
        $persisted = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('idempotency_key', 'key-race')
            ->sole();
        $this->assertSame((string) $winner->getKey(), (string) $persisted->getKey());
        $this->assertSame($winner->idempotency_hash, $persisted->idempotency_hash);
        $this->assertSame(2, Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->count());

        $response->assertStatus(409)->assertJsonPath('error.code', 'idempotency_key_conflict');
    }

    public function test_legacy_fingerprint_replays_after_a_schedule_change_but_checks_the_address(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $headers = array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'legacy-key']);
        $payload = $this->payload();
        $payload['address_snapshot'] = ['line1' => '12 Temple Road'];
        $first = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $payload, $headers)->assertCreated();
        $hash = hash('sha256', json_encode([
            'service_sku' => $payload['service_sku'],
            'scheduled_at' => $payload['scheduled_at'],
            'duration_minutes' => $payload['duration_minutes'],
            'address_id' => null,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        Booking::withoutGlobalScopes()->whereKey($first->json('data.id'))->update([
            'idempotency_hash' => $hash,
            'idempotency_hash_version' => 1,
            'scheduled_at' => '2030-02-01 10:00:00',
            'version' => 2,
        ]);

        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $payload, $headers)
            ->assertOk()->assertJsonPath('data.id', $first->json('data.id'))->assertJsonPath('data.version', 2);
        $payload['address_snapshot']['line1'] = '99 Other Road';
        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $payload, $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'idempotency_key_conflict');
        $this->assertDatabaseHas('bookings', ['id' => $first->json('data.id'), 'idempotency_hash' => $hash, 'idempotency_hash_version' => 1], 'tenant_shared');
    }

    public function test_an_unverifiable_legacy_key_is_not_replayed(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        $headers = array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'missing-hash']);
        $first = $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), $headers)->assertCreated();
        Booking::withoutGlobalScopes()->whereKey($first->json('data.id'))->update(['idempotency_hash' => null, 'idempotency_hash_version' => 1]);

        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'idempotency_key_conflict');
        $this->assertSame(1, Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->count());
    }

    public function test_creation_rolls_back_when_audit_storage_fails(): void
    {
        [$tenantId, $userId] = $this->bookingFixture();
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId);
        $user = \App\Models\User::query()->findOrFail($userId);
        \App\Platform\Audit\AuditLog::creating(function (\App\Platform\Audit\AuditLog $audit): void {
            if ($audit->action === 'booking.reservation.created') {
                throw new \RuntimeException('Audit storage unavailable.');
            }
        });

        $this->actingAs($user)->postJson('/api/v1/booking/reservations', $this->payload(), array_merge($this->tenantHeaders($tenant), ['Idempotency-Key' => 'audit-failure']))
            ->assertStatus(500);
        $this->assertDatabaseMissing('bookings', ['tenant_id' => $tenantId], 'tenant_shared');
        $this->assertDatabaseMissing('booking_transitions', ['tenant_id' => $tenantId], 'tenant_shared');
        $this->assertDatabaseMissing('outbox_events', ['tenant_id' => $tenantId, 'event_type' => 'booking.reservation.created'], 'tenant_shared');
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
