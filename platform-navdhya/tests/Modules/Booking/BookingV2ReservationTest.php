<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Booking V2 REST API Suite (WP1, WP2).
 * Verifies weak ETags, If-Match precondition enforcement, RFC 7807/9457 problem details,
 * read-only previews, and rebook flows.
 */
final class BookingV2ReservationTest extends TenantIntegrationTestCase
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

    public function test_v2_store_creates_draft_with_etag_and_version(): void
    {
        [$tenant, $user] = $this->customer();
        $sku = 'puja_ganesh_v2_' . Str::random(6);
        $this->seedCatalogItem($tenant, $sku);

        $headers = array_merge($this->tenantHeaders($tenant), [
            'Idempotency-Key' => (string) Str::uuid(),
        ]);

        $payload = [
            'service_sku' => $sku,
            'scheduled_at' => CarbonImmutable::now()->addDays(5)->toIso8601String(),
            'duration_minutes' => 90,
            'address_snapshot' => ['city' => 'Bengaluru', 'pincode' => '560001'],
        ];

        $response = $this->postJson('/api/v2/booking/reservations', $payload, $headers);

        $response->assertStatus(201)
            ->assertHeader('ETag', 'W/"v1"')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.service_sku', $sku)
            ->assertJsonPath('data.duration_minutes', 90);

        $bookingId = $response->json('data.id');
        $this->assertNotEmpty($bookingId);

        // Verify retrieval via show
        $showResponse = $this->getJson("/api/v2/booking/reservations/{$bookingId}", $headers);
        $showResponse->assertOk()
            ->assertHeader('ETag', 'W/"v1"')
            ->assertJsonPath('data.id', $bookingId)
            ->assertJsonPath('data.version', 1);
    }

    public function test_v2_show_unknown_or_foreign_booking_is_rfc7807_problem_details(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        $response = $this->getJson('/api/v2/booking/reservations/01JQZZZZZZZZZZZZZZZZZZZZZZ', $headers);

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404)
            ->assertJsonPath('code', 'not_found')
            ->assertJsonPath('title', 'Booking Not Found');
    }

    public function test_v2_confirm_enforces_if_match_preconditions(): void
    {
        [$tenant, $user] = $this->customer();
        $sku = 'puja_satya_v2_' . Str::random(6);
        $this->seedCatalogItem($tenant, $sku);

        $headers = $this->tenantHeaders($tenant);

        // Create booking
        $createResponse = $this->postJson('/api/v2/booking/reservations', [
            'service_sku' => $sku,
            'scheduled_at' => CarbonImmutable::now()->addDays(4)->toIso8601String(),
        ], array_merge($headers, ['Idempotency-Key' => (string) Str::uuid()]))->assertStatus(201);

        $bookingId = $createResponse->json('data.id');

        // Checkout booking to move to PENDING_PAYMENT
        $this->postJson("/api/v2/booking/reservations/{$bookingId}/checkout", [], array_merge($headers, ['Idempotency-Key' => (string) Str::uuid()]))
            ->assertOk();

        // 1. Confirm without If-Match header -> 412 precondition_required
        $noHeaderResponse = $this->postJson("/api/v2/booking/reservations/{$bookingId}/confirm", [], array_merge($headers, ['Idempotency-Key' => (string) Str::uuid()]));
        $noHeaderResponse->assertStatus(412)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'precondition_required');

        // 2. Confirm with mismatched If-Match header -> 412 precondition_failed
        $mismatchHeaders = array_merge($headers, ['If-Match' => 'W/"v99"', 'Idempotency-Key' => (string) Str::uuid()]);
        $mismatchResponse = $this->postJson("/api/v2/booking/reservations/{$bookingId}/confirm", [], $mismatchHeaders);
        $mismatchResponse->assertStatus(412)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'precondition_failed');

        // 3. Confirm with valid matching If-Match header -> 200 OK
        // Note: checkout incremented version from 1 to 2
        $validHeaders = array_merge($headers, ['If-Match' => 'W/"v2"', 'Idempotency-Key' => (string) Str::uuid()]);
        $confirmResponse = $this->postJson("/api/v2/booking/reservations/{$bookingId}/confirm", [], $validHeaders);
        $confirmResponse->assertOk()
            ->assertHeader('ETag', 'W/"v3"')
            ->assertJsonPath('data.status', 'CONFIRMED')
            ->assertJsonPath('data.version', 3);
    }

    public function test_v2_preview_cancellation_is_pure_read_only(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        // Seed confirmed booking 5 days in the future
        $this->resolveContext($tenant);
        $booking = Booking::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->id,
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::now()->addDays(5)->toDateTimeString(),
            'total_minor' => 10000,
            'version' => 2,
        ]);
        $this->clearContext();

        $bookingId = (string) $booking->getKey();

        $response = $this->postJson("/api/v2/booking/reservations/{$bookingId}/cancellation-preview", [], $headers);

        $response->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.policy_tier', 'FULL_REFUND')
            ->assertJsonPath('data.refund_minor', 10000)
            ->assertJsonPath('data.fee_minor', 0);

        // Invariant check: database state must remain completely unchanged
        $fresh = $booking->fresh();
        $this->assertSame(BookingLifecycle::STATUS_CONFIRMED, $fresh->status);
        $this->assertSame(2, (int) $fresh->version);
    }

    public function test_v2_cancel_enforces_if_match_and_increments_version(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        $this->resolveContext($tenant);
        $booking = Booking::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->id,
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::now()->addDays(4)->toDateTimeString(),
            'total_minor' => 8000,
            'version' => 2,
        ]);
        $this->clearContext();

        $bookingId = (string) $booking->getKey();

        // Missing If-Match -> 412
        $this->postJson("/api/v2/booking/reservations/{$bookingId}/cancel", [], $headers)
            ->assertStatus(412)
            ->assertJsonPath('code', 'precondition_required');

        // Matching If-Match -> 200
        $validHeaders = array_merge($headers, ['If-Match' => 'W/"v2"', 'Idempotency-Key' => (string) Str::uuid()]);
        $cancelResponse = $this->postJson("/api/v2/booking/reservations/{$bookingId}/cancel", [
            'reason' => 'Schedule change',
        ], $validHeaders);

        $cancelResponse->assertOk()
            ->assertHeader('ETag', 'W/"v3"')
            ->assertJsonPath('data.status', 'CANCELLED')
            ->assertJsonPath('data.version', 3);
    }

    public function test_v2_preview_reschedule_evaluates_availability_without_mutation(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        $this->resolveContext($tenant);
        $booking = Booking::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->id,
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::now()->addDays(3)->toDateTimeString(),
            'version' => 2,
        ]);
        $this->clearContext();

        $bookingId = (string) $booking->getKey();
        $newSlot = CarbonImmutable::now()->addDays(10)->toIso8601String();

        $response = $this->postJson("/api/v2/booking/reservations/{$bookingId}/reschedule-preview", [
            'new_scheduled_at' => $newSlot,
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.slot_available', true);

        // Ensure database state is unchanged
        $fresh = $booking->fresh();
        $this->assertSame(2, (int) $fresh->version);
        $this->assertSame(BookingLifecycle::STATUS_CONFIRMED, $fresh->status);
    }

    public function test_v2_rebook_creates_new_draft_reservation_from_previous(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        $sku = 'puja_lakshmi_v2_' . Str::random(6);
        $this->seedCatalogItem($tenant, $sku);

        $this->resolveContext($tenant);
        $sourceBooking = Booking::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->id,
            'service_sku' => $sku,
            'status' => BookingLifecycle::STATUS_COMPLETED,
            'duration_minutes' => 120,
            'scheduled_at' => CarbonImmutable::now()->subDays(10)->toDateTimeString(),
            'address_snapshot' => ['city' => 'Mysuru', 'pincode' => '570001'],
            'version' => 5,
        ]);
        $this->clearContext();

        $sourceId = (string) $sourceBooking->getKey();
        $newTime = CarbonImmutable::now()->addDays(7)->toIso8601String();

        $response = $this->postJson("/api/v2/booking/reservations/{$sourceId}/rebook", [
            'scheduled_at' => $newTime,
            'duration_minutes' => 120,
        ], array_merge($headers, ['Idempotency-Key' => (string) Str::uuid()]));

        $response->assertStatus(201)
            ->assertHeader('ETag', 'W/"v1"')
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.service_sku', $sku)
            ->assertJsonPath('data.duration_minutes', 120);

        $newBookingId = $response->json('data.id');
        $this->assertNotEmpty($newBookingId);
        $this->assertNotSame($sourceId, $newBookingId);
    }

    public function test_v2_mutations_fail_closed_400_when_idempotency_key_missing(): void
    {
        [$tenant, $user] = $this->customer();
        $sku = 'puja_failclosed_' . Str::random(6);
        $this->seedCatalogItem($tenant, $sku);
        $headers = $this->tenantHeaders($tenant);

        // 1. Reserve without Idempotency-Key -> 400
        $this->postJson('/api/v2/booking/reservations', [
            'service_sku' => $sku,
            'scheduled_at' => CarbonImmutable::now()->addDays(4)->toIso8601String(),
        ], $headers)
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'idempotency_key_required');

        $this->resolveContext($tenant);
        $booking = Booking::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->id,
            'service_sku' => $sku,
            'status' => BookingLifecycle::STATUS_DRAFT,
            'scheduled_at' => CarbonImmutable::now()->addDays(4)->toDateTimeString(),
            'version' => 1,
        ]);
        $confirmed = Booking::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->id,
            'service_sku' => $sku,
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::now()->addDays(5)->toDateTimeString(),
            'version' => 2,
        ]);
        $completed = Booking::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->id,
            'service_sku' => $sku,
            'status' => BookingLifecycle::STATUS_COMPLETED,
            'scheduled_at' => CarbonImmutable::now()->subDays(2)->toDateTimeString(),
            'version' => 3,
        ]);
        $this->clearContext();

        // 2. Checkout without Idempotency-Key -> 400
        $this->postJson("/api/v2/booking/reservations/{$booking->getKey()}/checkout", [], $headers)
            ->assertStatus(400)
            ->assertJsonPath('code', 'idempotency_key_required');

        // 3. Confirm without Idempotency-Key (with If-Match present) -> 400
        $this->postJson("/api/v2/booking/reservations/{$booking->getKey()}/confirm", [], array_merge($headers, ['If-Match' => 'W/"v1"']))
            ->assertStatus(400)
            ->assertJsonPath('code', 'idempotency_key_required');

        // 4. Cancel without Idempotency-Key (with If-Match present) -> 400
        $this->postJson("/api/v2/booking/reservations/{$confirmed->getKey()}/cancel", [], array_merge($headers, ['If-Match' => 'W/"v2"']))
            ->assertStatus(400)
            ->assertJsonPath('code', 'idempotency_key_required');

        // 5. Reschedule without Idempotency-Key (with If-Match present) -> 400
        $this->postJson("/api/v2/booking/reservations/{$confirmed->getKey()}/reschedule", [
            'new_scheduled_at' => CarbonImmutable::now()->addDays(6)->toIso8601String(),
        ], array_merge($headers, ['If-Match' => 'W/"v2"']))
            ->assertStatus(400)
            ->assertJsonPath('code', 'idempotency_key_required');

        // 6. Rebook without Idempotency-Key -> 400
        $this->postJson("/api/v2/booking/reservations/{$completed->getKey()}/rebook", [
            'scheduled_at' => CarbonImmutable::now()->addDays(7)->toIso8601String(),
        ], $headers)
            ->assertStatus(400)
            ->assertJsonPath('code', 'idempotency_key_required');
    }
}
