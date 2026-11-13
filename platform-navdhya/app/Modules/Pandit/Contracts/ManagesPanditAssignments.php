<?php

namespace App\Modules\Pandit\Contracts;

/**
 * Pandit assignment management port (deptrac-blessed Contracts surface).
 * Ambient tenant context scopes every call.
 *
 * @phpstan-type PanditAssignmentData array{id: string, status: string, version: int, assigned_at: string, confirmed_at: string|null, service_started_at: string|null, declined_at: string|null, decline_reason: string|null, checklist: array<string, mixed>|null, compensation_minor: int|null, booking: array{id: string, user_id: int, service_sku: string|null, status: string, scheduled_at: string|null, duration_minutes: int}|null}
 * @phpstan-type PanditAssignmentPage array{limit: int, has_more: bool, next_cursor: string|null}
 */
interface ManagesPanditAssignments
{
    /**
     * Assign pandit to booking. Idempotent per (pandit, booking).
     *
     * @throws \App\Modules\Pandit\UnknownBookingException when the booking is unknown in this tenant
     * @throws \InvalidArgumentException when the windows overlap an existing assignment
     *
     * @return PanditAssignmentData
     */
    public function assign(string $panditId, string $bookingId, int $compensationMinor): array;

    /**
     * Accept the assignment: the booking follows through the fulfillment
     * port (CONFIRMED → ASSIGNED) in the same transaction — a booking
     * that no longer wants the work refuses the whole verb.
     *
     * @throws \App\Modules\Pandit\AssignmentNotFoundException when the assignment is unknown or foreign
     * @throws \InvalidArgumentException when the assignment is no longer PENDING
     * @throws \App\Modules\Pandit\BookingWrongStateException when the booking's lifecycle state refuses the acceptance
     *
     * @return PanditAssignmentData
     */
    public function confirm(string $panditId, string $assignmentId): array;

    /**
     * Begin performing the service: CONFIRMED → IN_PROGRESS, driving the
     * booking ASSIGNED → IN_PROGRESS in the same transaction.
     *
     * @param  array<string, mixed>  $checklist
     *
     * @throws \App\Modules\Pandit\AssignmentNotFoundException when the assignment is unknown or foreign
     * @throws \InvalidArgumentException when the assignment is not CONFIRMED or modified concurrently
     * @throws \App\Modules\Pandit\BookingWrongStateException when the booking's lifecycle state refuses start
     *
     * @return PanditAssignmentData
     */
    public function start(string $panditId, string $assignmentId, array $checklist = []): array;

    /**
     * Complete the work: assignment → COMPLETED and the booking follows
     * through the fulfillment port (ASSIGNED|IN_PROGRESS → COMPLETED)
     * in the same transaction.
     *
     * @throws \App\Modules\Pandit\AssignmentNotFoundException when the assignment is unknown or foreign
     * @throws \InvalidArgumentException when the assignment is no longer CONFIRMED or IN_PROGRESS
     * @throws \App\Modules\Pandit\BookingWrongStateException when the booking's lifecycle state refuses the completion
     *
     * @return PanditAssignmentData
     */
    public function complete(string $panditId, string $assignmentId): array;

    /**
     * @throws \App\Modules\Pandit\AssignmentNotFoundException when the assignment is unknown or foreign
     * @throws \InvalidArgumentException when the assignment is no longer PENDING
     *
     * @return PanditAssignmentData
     */
    public function decline(string $panditId, string $assignmentId, string $reason): array;

    /**
     * @param  string|null  $status
     * @param  string|null  $cursor  opaque cursor from a previous page (null = first page)
     * @return array{items: list<PanditAssignmentData>, page: PanditAssignmentPage}
     */
    public function getAssignments(string $panditId, ?string $status = null, int $limit = 20, ?string $cursor = null): array;

    /**
     * @return PanditAssignmentData
     */
    public function adminAssign(string $panditId, string $bookingId, int $compensationMinor): array;

    /**
     * @return void
     */
    public function adminUnassign(string $assignmentId): void;

    /**
     * Whether the pandit already holds a PENDING or CONFIRMED assignment
     * whose booking window intersects [startIso, endIso) (B2 instant-book
     * search — the read-only twin of assign()'s fail-closed overlap
     * guard). Unknown/unscheduled windows never conflict.
     */
    public function hasWindowConflict(string $panditId, string $startIso, string $endIso): bool;

    /**
     * Resolve the confirmed/completed (or active) pandit assignment for a
     * booking inside the ambient tenant (TS-01 / TS-08 server-derived
     * subject & arrival actor resolution).
     *
     * @return array{id: string, pandit_id: string, booking_id: string, status: string, pandit_user_id: int|null}|null
     */
    public function assignmentForBooking(string $bookingId): ?array;

    /**
     * Cancel any active assignment on $bookingId and assign $replacementPanditId
     * atomically inside an incident recovery flow (TS-13 / E-TS-06).
     *
     * @return PanditAssignmentData
     */
    public function reassignForIncident(string $bookingId, string $replacementPanditId, int $compensationMinor = 0): array;
}
