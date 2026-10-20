<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * B4 fulfillment (backend authority): the pandit self-serve verbs drive
 * the booking lifecycle through the TransitionsBookingFulfillment port —
 * :confirm accepts (CONFIRMED → ASSIGNED), :complete completes
 * (ASSIGNED → IN_PROGRESS → COMPLETED, both matrix hops recorded), and
 * every refusal rolls the assignment back (nothing written — replay and
 * double-accept safety).
 */
final class BookingFulfillmentTest extends TenantIntegrationTestCase
{
    private const COMPENSATION_MINOR = 50000;

    /**
     * @return array{0: Tenant, 1: User, 2: string}
     */
    private function pandit(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);

        try {
            $profileId = (string) PanditProfile::factory()->create([
                'user_id' => $user->getAuthIdentifier(),
            ])->getKey();
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user, $profileId];
    }

    private function seedBooking(Tenant $tenant, string $status = BookingLifecycle::STATUS_CONFIRMED): string
    {
        $this->resolveContext($tenant);

        try {
            return (string) Booking::factory()->create([
                'status' => $status,
                'scheduled_at' => now()->addDays(5)->toDateTimeString(),
                'duration_minutes' => 60,
            ])->getKey();
        } finally {
            $this->clearContext();
        }
    }

    private function seedAssignment(Tenant $tenant, string $panditId, string $bookingId, string $status = PanditAssignment::STATUS_PENDING): PanditAssignment
    {
        $this->resolveContext($tenant);

        try {
            return PanditAssignment::factory()->create([
                'pandit_id' => $panditId,
                'booking_id' => $bookingId,
                'status' => $status,
                'compensation_minor' => self::COMPENSATION_MINOR,
            ]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_assignment_accept_moves_booking_to_assigned(): void
    {
        [$tenant, $user, $panditId] = $this->pandit();
        $headers = $this->tenantHeaders($tenant);

        $bookingId = $this->seedBooking($tenant);
        $assignment = $this->seedAssignment($tenant, $panditId, $bookingId);
        $id = (string) $assignment->getKey();

        $this->postJson("/api/v1/pandit/assignments/{$id}:confirm", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'CONFIRMED');

        $booking = Booking::withoutGlobalScopes()->findOrFail($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_ASSIGNED, $booking->status);
        $this->assertSame(2, $booking->version);

        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $bookingId,
            'from_status' => BookingLifecycle::STATUS_CONFIRMED,
            'to_status' => BookingLifecycle::STATUS_ASSIGNED,
            'reason' => 'assignment_accepted',
            'actor_id' => (string) $user->getAuthIdentifier(),
        ], 'tenant_shared');

        $this->assertAuditRecorded($tenant, 'pandit.assignment.confirmed');
        $this->assertAuditRecorded($tenant, 'booking.fulfillment.assigned');
    }

    public function test_assignment_accept_is_refused_when_booking_no_longer_wants_the_work(): void
    {
        [$tenant, , $panditId] = $this->pandit();
        $headers = $this->tenantHeaders($tenant);

        // The customer cancelled before the pandit answered — the
        // acceptance fact no longer applies and rolls back entirely.
        $bookingId = $this->seedBooking($tenant, BookingLifecycle::STATUS_CANCELLED);
        $assignment = $this->seedAssignment($tenant, $panditId, $bookingId);
        $id = (string) $assignment->getKey();

        $this->postJson("/api/v1/pandit/assignments/{$id}:confirm", [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PANDIT_BOOKING_WRONG_STATE');

        $this->assertSame(
            PanditAssignment::STATUS_PENDING,
            PanditAssignment::query()->withoutGlobalScopes()->findOrFail($id)->status,
        );

        $booking = Booking::withoutGlobalScopes()->findOrFail($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_CANCELLED, $booking->status);
        $this->assertSame(1, $booking->version);
        $this->assertDatabaseCount('booking_transitions', 0, 'tenant_shared');
    }

    public function test_second_pandit_accept_is_refused_once_the_booking_is_assigned(): void
    {
        [$tenant, , $panditA] = $this->pandit();
        $headersA = $this->tenantHeaders($tenant);
        $bookingId = $this->seedBooking($tenant);
        $assignmentA = (string) $this->seedAssignment($tenant, $panditA, $bookingId)->getKey();

        // A second pandit holds their own pending assignment on the same
        // booking (per-pandit uniqueness — cross-pandit is allowed).
        $userB = User::factory()->create();
        $this->makeMembership($tenant, $userB);
        $this->resolveContext($tenant);

        try {
            $panditB = (string) PanditProfile::factory()->create([
                'user_id' => $userB->getAuthIdentifier(),
            ])->getKey();
            $assignmentB = (string) PanditAssignment::factory()->create([
                'pandit_id' => $panditB,
                'booking_id' => $bookingId,
                'status' => PanditAssignment::STATUS_PENDING,
                'compensation_minor' => self::COMPENSATION_MINOR,
            ])->getKey();
        } finally {
            $this->clearContext();
        }

        $this->postJson("/api/v1/pandit/assignments/{$assignmentA}:confirm", [], $headersA)
            ->assertOk();

        $this->actingAs($userB);
        $this->postJson("/api/v1/pandit/assignments/{$assignmentB}:confirm", [], $this->tenantHeaders($tenant))
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PANDIT_BOOKING_WRONG_STATE');

        $this->assertSame(
            PanditAssignment::STATUS_PENDING,
            PanditAssignment::query()->withoutGlobalScopes()->findOrFail($assignmentB)->status,
        );

        $booking = Booking::withoutGlobalScopes()->findOrFail($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_ASSIGNED, $booking->status);
        $this->assertSame(2, $booking->version);
    }

    public function test_complete_drives_assigned_to_completed_and_counts_in_earnings(): void
    {
        [$tenant, $user, $panditId] = $this->pandit();
        $headers = $this->tenantHeaders($tenant);

        $bookingId = $this->seedBooking($tenant);
        $assignment = $this->seedAssignment($tenant, $panditId, $bookingId);
        $id = (string) $assignment->getKey();

        $this->postJson("/api/v1/pandit/assignments/{$id}:confirm", [], $headers)->assertOk();

        $this->postJson("/api/v1/pandit/assignments/{$id}:complete", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'COMPLETED');

        $booking = Booking::withoutGlobalScopes()->findOrFail($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_COMPLETED, $booking->status);
        $this->assertSame(3, $booking->version);

        // Both matrix hops recorded (ASSIGNED → IN_PROGRESS → COMPLETED).
        $actor = (string) $user->getAuthIdentifier();
        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $bookingId,
            'from_status' => BookingLifecycle::STATUS_ASSIGNED,
            'to_status' => BookingLifecycle::STATUS_IN_PROGRESS,
            'reason' => 'assignment_completed',
            'actor_id' => $actor,
        ], 'tenant_shared');
        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $bookingId,
            'from_status' => BookingLifecycle::STATUS_IN_PROGRESS,
            'to_status' => BookingLifecycle::STATUS_COMPLETED,
            'reason' => 'assignment_completed',
            'actor_id' => $actor,
        ], 'tenant_shared');

        $this->assertAuditRecorded($tenant, 'pandit.assignment.completed');
        $this->assertAuditRecorded($tenant, 'booking.fulfillment.completed');

        // COMPLETED assignments are what earnings count (read-only v1).
        $earnings = $this->getJson('/api/v1/pandit/earnings', $headers)->assertOk();
        $this->assertSame(1, $earnings->json('data.completed_assignments_count'));
        $this->assertSame(self::COMPENSATION_MINOR, $earnings->json('data.total_earnings_minor'));
    }

    public function test_complete_gates_on_assignment_and_booking_state(): void
    {
        [$tenant, , $panditId] = $this->pandit();
        $headers = $this->tenantHeaders($tenant);

        // Only a CONFIRMED assignment can complete.
        $pendingBooking = $this->seedBooking($tenant);
        $pending = (string) $this->seedAssignment($tenant, $panditId, $pendingBooking)->getKey();

        $this->postJson("/api/v1/pandit/assignments/{$pending}:complete", [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PANDIT_ASSIGNMENT_WRONG_STATE');

        // A booking cancelled after assignment refuses completion; the
        // assignment stays CONFIRMED (resolution path: ops/refund track).
        $bookingId = $this->seedBooking($tenant, BookingLifecycle::STATUS_CANCELLED);
        $assignment = $this->seedAssignment($tenant, $panditId, $bookingId, PanditAssignment::STATUS_CONFIRMED);
        $id = (string) $assignment->getKey();

        $this->postJson("/api/v1/pandit/assignments/{$id}:complete", [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PANDIT_BOOKING_WRONG_STATE');

        $this->assertSame(
            PanditAssignment::STATUS_CONFIRMED,
            PanditAssignment::query()->withoutGlobalScopes()->findOrFail($id)->status,
        );

        $booking = Booking::withoutGlobalScopes()->findOrFail($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_CANCELLED, $booking->status);
        $this->assertDatabaseCount('booking_transitions', 0, 'tenant_shared');

        // Unknown or foreign ids stay 404 (ownership is the actor).
        $this->postJson('/api/v1/pandit/assignments/01JQZZZZZZZZZZZZZZZZZZZZZZ:complete', [], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'PANDIT_ASSIGNMENT_NOT_FOUND');
    }

    public function test_fulfillment_verbs_require_authentication(): void
    {
        $tenant = $this->makeTenant();
        $headers = $this->tenantHeaders($tenant);
        auth()->logout();

        foreach (['confirm', 'complete'] as $verb) {
            $this->postJson("/api/v1/pandit/assignments/01JQZZZZZZZZZZZZZZZZZZZZZ:{$verb}", [], $headers)
                ->assertUnauthorized();
        }
    }
}
