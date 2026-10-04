<?php

namespace App\Modules\Pandit\Contracts;

/**
 * Pandit assignment management port (deptrac-blessed Contracts surface).
 * Ambient tenant context scopes every call.
 *
 * @phpstan-type PanditAssignmentData array{id: string, status: string, assigned_at: string, confirmed_at: string|null, declined_at: string|null, decline_reason: string|null, compensation_minor: int|null, booking: array{id: string, user_id: int, service_sku: string|null, status: string, scheduled_at: string|null, duration_minutes: int}|null}
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
     * @throws \App\Modules\Pandit\AssignmentNotFoundException when the assignment is unknown or foreign
     * @throws \InvalidArgumentException when the assignment is no longer PENDING
     *
     * @return PanditAssignmentData
     */
    public function confirm(string $panditId, string $assignmentId): array;

    /**
     * @throws \App\Modules\Pandit\AssignmentNotFoundException when the assignment is unknown or foreign
     * @throws \InvalidArgumentException when the assignment is no longer PENDING
     *
     * @return PanditAssignmentData
     */
    public function decline(string $panditId, string $assignmentId, string $reason): array;

    /**
     * @param  string|null  $status
     * @return array{items: list<PanditAssignmentData>, page: PanditAssignmentPage}
     */
    public function getAssignments(string $panditId, ?string $status = null, int $limit = 20): array;

    /**
     * @return PanditAssignmentData
     */
    public function adminAssign(string $panditId, string $bookingId, int $compensationMinor): array;

    /**
     * @return void
     */
    public function adminUnassign(string $assignmentId): void;
}
