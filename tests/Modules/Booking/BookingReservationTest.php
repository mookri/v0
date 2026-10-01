<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Reservations (B5, docs/api/parity-customers.md): sectioned cursor
 * lists, live actions, cancel gates + idempotent replay, reschedule
 * version/overlap/state gates, transition history, and the booking→
 * notification producer path.
 */
final class BookingReservationTest extends TenantIntegrationTestCase
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

    private function seedBooking(Tenant $tenant, User $user, string $status, ?string $scheduledAt = null): Booking
    {
        $this->resolveContext($tenant);
        try {
            return Booking::factory()->create([
                'user_id' => $user->id,
                'status' => $status,
                'scheduled_at' => $scheduledAt ?? now()->addDays(3)->toDateTimeString(),
            ]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_list_sections_and_show_actions(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED);
        $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_IN_PROGRESS);
        $done = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_COMPLETED);
        $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CANCELLED);

        $all = $actor()->getJson('/api/v1/booking/reservations', $headers)->assertOk();
        $this->assertCount(4, $all->json('data'));

        $active = $actor()->getJson('/api/v1/booking/reservations?section=active', $headers)->assertOk();
        $this->assertCount(1, $active->json('data'));
        $this->assertSame('IN_PROGRESS', $active->json('data.0.status'));

        // Unknown sections are ignored, not errors.
        $actor()->getJson('/api/v1/booking/reservations?section=nope', $headers)->assertOk()
            ->assertJsonCount(4, 'data');

        $actor()->getJson('/api/v1/booking/reservations/'.(string) $done->getKey(), $headers)
            ->assertOk()->assertJsonPath('data.actions', []);
    }

    public function test_cancel_happy_idempotent_and_illegal(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);
        $tid = (string) $tenant->getKey();

        $booking = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED);
        $id = (string) $booking->getKey();

        $cancelled = $actor()->postJson("/api/v1/booking/reservations/{$id}:cancel", [], $headers)
            ->assertOk()->assertJsonPath('data.status', 'CANCELLED');
        $this->assertSame([], $cancelled->json('data.actions'));
        $this->assertSame(2, $cancelled->json('data.version'));

        // Transition row + audit + customer notification produced.
        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $id, 'from_status' => 'CONFIRMED', 'to_status' => 'CANCELLED',
        ], 'tenant_shared');
        $this->assertAuditRecorded($tenant, 'booking.reservation.cancelled');
        $this->assertDatabaseHas('customer_notifications', [
            'tenant_id' => $tid, 'user_id' => $user->getAuthIdentifier(), 'kind' => 'booking',
        ], 'tenant_shared');

        // Idempotent replay: same representation, no second transition row.
        $actor()->postJson("/api/v1/booking/reservations/{$id}:cancel", [], $headers)->assertOk();
        $this->assertSame(1, BookingTransition::query()
            ->withoutGlobalScopes()->where('booking_id', $id)->count());

        // Off-lifecycle states refuse.
        $done = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_COMPLETED);
        $actor()->postJson('/api/v1/booking/reservations/'.(string) $done->getKey().':cancel', [], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'state_forbidden');
    }

    public function test_reschedule_guards(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $slot = CarbonImmutable::parse('2030-05-01 10:00:00');
        $booking = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED, $slot->toDateTimeString());
        $id = (string) $booking->getKey();
        $move = fn (string $when, int $version) => $actor()->postJson(
            "/api/v1/booking/reservations/{$id}:reschedule",
            ['new_start_time' => $when, 'version' => $version],
            $headers
        );

        // Stale version first (proves the lock before any state changes).
        $move('2030-05-02 10:00:00', 99)->assertStatus(409)
            ->assertJsonPath('error.code', 'version_conflict');

        // Happy path.
        $moved = $move('2030-05-02 10:00:00', 1)->assertOk();
        $this->assertSame(2, $moved->json('data.version'));
        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $id, 'from_status' => 'CONFIRMED', 'to_status' => 'CONFIRMED', 'reason' => 'rescheduled',
        ], 'tenant_shared');

        // Overlap: a second booking holds the target window.
        $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_ASSIGNED, '2030-05-03 10:30:00');
        $move('2030-05-03 10:00:00', 2)->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');

        // Off-lifecycle states refuse.
        $done = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_COMPLETED);
        $actor()->postJson('/api/v1/booking/reservations/'.(string) $done->getKey().':reschedule', [
            'new_start_time' => '2030-06-01 10:00:00', 'version' => 1,
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'state_forbidden');
    }

    public function test_history_foreign_and_guests(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $booking = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED);
        $id = (string) $booking->getKey();
        $actor()->postJson("/api/v1/booking/reservations/{$id}:cancel", [], $headers)->assertOk();

        $history = $actor()->getJson("/api/v1/booking/reservations/{$id}/history", $headers)->assertOk();
        $this->assertSame([
            ['from' => 'CONFIRMED', 'to' => 'CANCELLED'],
        ], collect($history->json('data'))->map(fn ($row): array => [
            'from' => $row['from'], 'to' => $row['to'],
        ])->all());

        // Foreign bookings resolve to nothing everywhere.
        $other = $this->makeTenant();
        [$otherUser] = $this->actingAsMembership($other);
        $foreign = $this->seedBooking($other, $otherUser, BookingLifecycle::STATUS_CONFIRMED);
        $fid = (string) $foreign->getKey();

        $actor()->getJson("/api/v1/booking/reservations/{$fid}", $headers)->assertNotFound();
        $actor()->postJson("/api/v1/booking/reservations/{$fid}:cancel", [], $headers)->assertNotFound();
        $actor()->getJson("/api/v1/booking/reservations/{$fid}/history", $headers)->assertNotFound();

        auth()->logout();
        $this->getJson('/api/v1/booking/reservations', $headers)->assertUnauthorized();
    }
}
