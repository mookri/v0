<?php

namespace App\Modules\Pandit;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Booking\Contracts\TransitionsBookingFulfillment;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Pandit assignment management (deptrac-blessed Contracts implementation).
 * Booking rows are resolved through the ReadsBookings port and booking
 * STATE changes flow through the TransitionsBookingFulfillment port — no
 * booking model import. Ambient tenant context scopes every call.
 *
 * @phpstan-import-type PanditAssignmentData from ManagesPanditAssignments
 * @phpstan-import-type PanditAssignmentPage from ManagesPanditAssignments
 */
final class PanditAssignmentManager implements ManagesPanditAssignments
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly ReadsBookings $bookings,
        private readonly TransitionsBookingFulfillment $fulfillment,
    ) {
    }

    /**
     * @return PanditAssignmentData
     */
    public function assign(string $panditId, string $bookingId, int $compensationMinor): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        // Verify pandit exists and belongs to tenant
        PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $panditId)
            ->firstOrFail();

        $window = $this->bookings->windowsByIds([$bookingId])[$bookingId] ?? null;

        if ($window === null) {
            throw new UnknownBookingException('Unknown booking.');
        }

        // Idempotent: an existing assignment is returned unchanged
        $existing = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId)
            ->where('booking_id', $bookingId)
            ->first();

        if ($existing !== null) {
            return $this->serialize($existing, $this->bookings->windowsByIds([$bookingId]));
        }

        // Fail-closed overlap guard before any write
        $this->checkConflict($tenantId, $panditId, $bookingId, $window);

        $assignment = (new \App\Modules\Pandit\Models\PanditAssignment())->getConnection()->transaction(function () use ($tenantId, $panditId, $bookingId, $compensationMinor) {
            $assignment = new PanditAssignment();
            $assignment->forceFill([
                'tenant_id' => $tenantId,
                'pandit_id' => $panditId,
                'booking_id' => $bookingId,
                'status' => PanditAssignment::STATUS_PENDING,
                'assigned_at' => CarbonImmutable::now()->toDateTimeString(),
                'compensation_minor' => $compensationMinor,
            ]);
            $assignment->save();

            return $assignment;
        });

        $this->audit->record(
            'pandit.assignment.assigned',
            ['module' => 'pandit', 'resource_type' => 'pandit_assignment', 'resource_id' => (string) $assignment->getKey()],
            null,
            ['pandit_id' => $panditId, 'booking_id' => $bookingId, 'compensation_minor' => $compensationMinor],
        );

        return $this->serialize($assignment, $this->bookings->windowsByIds([$bookingId]));
    }

    /**
     * @return PanditAssignmentData
     */
    public function confirm(string $panditId, string $assignmentId): array
    {
        $assignment = $this->ownedInStatus($panditId, $assignmentId, PanditAssignment::STATUS_PENDING, 'Can only act on pending assignments.');

        // The transaction must wrap the connection the models actually
        // write on — assignment and booking both follow the ambient
        // tenant (TEN-012), never the default connection.
        $assignment->getConnection()->transaction(function () use ($assignment, $panditId): void {
            $assignment->forceFill([
                'status' => PanditAssignment::STATUS_CONFIRMED,
                'confirmed_at' => CarbonImmutable::now()->toDateTimeString(),
            ])->save();

            // One fact, two module states: the acceptance moves the booking
            // CONFIRMED → ASSIGNED through the lifecycle port. A booking
            // that no longer wants the work refuses the verb and rolls the
            // assignment back (resolution path: admin unassign).
            $outcome = $this->fulfillment->assignmentAccepted(
                (string) $assignment->booking_id,
                $this->panditUserId($panditId),
            );

            if ($outcome['ok'] !== true) {
                throw new BookingWrongStateException(
                    'Booking is not in a state that accepts fulfillment.',
                    $outcome['from'] ?? '',
                );
            }
        });

        $this->audit->record(
            'pandit.assignment.confirmed',
            ['module' => 'pandit', 'resource_type' => 'pandit_assignment', 'resource_id' => (string) $assignment->getKey()],
            null,
            ['pandit_id' => $panditId, 'booking_id' => (string) $assignment->booking_id],
        );

        return $this->serialize($assignment, $this->bookings->windowsByIds([(string) $assignment->booking_id]));
    }

    /**
     * @return PanditAssignmentData
     */
    public function complete(string $panditId, string $assignmentId): array
    {
        $assignment = $this->ownedInStatus($panditId, $assignmentId, PanditAssignment::STATUS_CONFIRMED, 'Can only complete a confirmed assignment.');

        // Same ambient-connection transaction as confirm(): the
        // assignment status change and the booking lifecycle hops commit
        // together or not at all.
        $assignment->getConnection()->transaction(function () use ($assignment, $panditId): void {
            $assignment->forceFill([
                'status' => PanditAssignment::STATUS_COMPLETED,
            ])->save();

            // One fact, two module states: completion drives the booking
            // ASSIGNED → IN_PROGRESS → COMPLETED through the lifecycle
            // port (both matrix hops, one recorded fact).
            $outcome = $this->fulfillment->assignmentCompleted(
                (string) $assignment->booking_id,
                $this->panditUserId($panditId),
            );

            if ($outcome['ok'] !== true) {
                throw new BookingWrongStateException(
                    'Booking is not in a state that accepts completion.',
                    $outcome['from'] ?? '',
                );
            }
        });

        $this->audit->record(
            'pandit.assignment.completed',
            ['module' => 'pandit', 'resource_type' => 'pandit_assignment', 'resource_id' => (string) $assignment->getKey()],
            null,
            ['pandit_id' => $panditId, 'booking_id' => (string) $assignment->booking_id],
        );

        return $this->serialize($assignment, $this->bookings->windowsByIds([(string) $assignment->booking_id]));
    }

    /**
     * @return PanditAssignmentData
     */
    public function decline(string $panditId, string $assignmentId, string $reason): array
    {
        $assignment = $this->ownedInStatus($panditId, $assignmentId, PanditAssignment::STATUS_PENDING, 'Can only act on pending assignments.');

        $assignment->forceFill([
            'status' => PanditAssignment::STATUS_DECLINED,
            'declined_at' => CarbonImmutable::now()->toDateTimeString(),
            'decline_reason' => $reason,
        ])->save();

        $this->audit->record(
            'pandit.assignment.declined',
            ['module' => 'pandit', 'resource_type' => 'pandit_assignment', 'resource_id' => (string) $assignment->getKey()],
            null,
            ['pandit_id' => $panditId, 'booking_id' => (string) $assignment->booking_id, 'reason' => $reason],
        );

        return $this->serialize($assignment, $this->bookings->windowsByIds([(string) $assignment->booking_id]));
    }

    /**
     * @param  string|null  $status
     * @return array{items: list<PanditAssignmentData>, page: PanditAssignmentPage}
     */
    public function getAssignments(string $panditId, ?string $status = null, int $limit = 20, ?string $cursor = null): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $query = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        $request = new Request(['limit' => $limit, 'cursor' => $cursor]);
        [$items, $page] = CursorPagination::paginate($query, $request);

        $windows = $this->bookings->windowsByIds(
            array_map(static fn (PanditAssignment $a): string => (string) $a->booking_id, $items),
        );

        return [
            'items' => array_map(fn (PanditAssignment $a): array => $this->serialize($a, $windows), $items),
            'page' => $page,
        ];
    }

    /**
     * @return PanditAssignmentData
     */
    public function adminAssign(string $panditId, string $bookingId, int $compensationMinor): array
    {
        return $this->assign($panditId, $bookingId, $compensationMinor);
    }

    public function adminUnassign(string $assignmentId): void
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $assignment = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $assignmentId)
            ->firstOrFail();

        $this->audit->record(
            'pandit.assignment.unassigned',
            ['module' => 'pandit', 'resource_type' => 'pandit_assignment', 'resource_id' => (string) $assignment->getKey()],
            null,
            ['pandit_id' => (string) $assignment->pandit_id, 'booking_id' => (string) $assignment->booking_id],
        );

        $assignment->delete();
    }

    /**
     * Read-only twin of the assign() overlap guard (B2 instant-book
     * search): does any PENDING/CONFIRMED assignment of this pandit
     * occupy the requested window?
     */
    public function hasWindowConflict(string $panditId, string $startIso, string $endIso): bool
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        return $this->conflictExists(
            $tenantId,
            $panditId,
            CarbonImmutable::parse($startIso),
            CarbonImmutable::parse($endIso),
            null,
        );
    }

    /**
     * The caller's own assignment in the required status (404 for
     * foreign rows — ownership is the actor — 409 for any other state).
     *
     * @return PanditAssignment
     */
    private function ownedInStatus(string $panditId, string $assignmentId, string $status, string $message): PanditAssignment
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $assignment = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId)
            ->where('id', $assignmentId)
            ->first();

        if ($assignment === null) {
            throw new AssignmentNotFoundException('Assignment not found.');
        }

        if ($assignment->status !== $status) {
            throw new \InvalidArgumentException($message);
        }

        return $assignment;
    }

    /**
     * The acting principal's user id — attributed on the booking
     * transition rows (K-029; contrast the payment port's system actor).
     * The profile row always exists here (ownership was just verified on
     * the assignment); the fallback keeps the actor stable regardless.
     */
    private function panditUserId(string $panditId): string
    {
        $userId = PanditProfile::query()
            ->where('tenant_id', (string) app(TenantContext::class)->tenantId())
            ->where('id', $panditId)
            ->value('user_id');

        return $userId !== null ? (string) $userId : 'system:pandit';
    }

    /**
     * Fail-closed overlap guard: the pandit must not already hold a pending
     * or confirmed assignment whose booking window intersects this one.
     *
     * @param  array{id: string, user_id: int, service_sku: string|null, status: string, scheduled_at: string|null, duration_minutes: int}  $window
     */
    private function checkConflict(string $tenantId, string $panditId, string $bookingId, array $window): void
    {
        if ($window['scheduled_at'] === null) {
            return; // unscheduled booking — nothing to overlap
        }

        $start = CarbonImmutable::parse($window['scheduled_at']);
        $end = $start->addMinutes($window['duration_minutes']);

        if ($this->conflictExists($tenantId, $panditId, $start, $end, $bookingId)) {
            throw new \InvalidArgumentException('Pandit has a scheduling conflict with another booking.');
        }
    }

    /**
     * Shared overlap probe behind checkConflict() (write path, excludes
     * the booking being assigned) and hasWindowConflict() (B2 search read
     * path, no exclusion).
     *
     * @param  string|null  $excludeBookingId
     */
    private function conflictExists(string $tenantId, string $panditId, CarbonImmutable $start, CarbonImmutable $end, ?string $excludeBookingId): bool
    {
        $query = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId)
            ->whereIn('status', [PanditAssignment::STATUS_PENDING, PanditAssignment::STATUS_CONFIRMED]);

        if ($excludeBookingId !== null) {
            $query->where('booking_id', '!=', $excludeBookingId);
        }

        $otherIds = $query->pluck('booking_id')->all();

        foreach ($this->bookings->windowsByIds($otherIds) as $other) {
            if ($other['scheduled_at'] === null) {
                continue;
            }

            $otherStart = CarbonImmutable::parse($other['scheduled_at']);
            $otherEnd = $otherStart->addMinutes($other['duration_minutes']);

            if ($otherStart < $end && $start < $otherEnd) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array{id: string, user_id: int, service_sku: string|null, status: string, scheduled_at: string|null, duration_minutes: int}>  $windows
     * @return PanditAssignmentData
     */
    private function serialize(PanditAssignment $assignment, array $windows): array
    {
        return [
            'id' => (string) $assignment->getKey(),
            'status' => $assignment->status,
            'assigned_at' => $assignment->assigned_at->toJSON(),
            'confirmed_at' => $assignment->confirmed_at?->toJSON(),
            'declined_at' => $assignment->declined_at?->toJSON(),
            'decline_reason' => $assignment->decline_reason,
            'compensation_minor' => $assignment->compensation_minor,
            'booking' => $windows[(string) $assignment->booking_id] ?? null,
        ];
    }
}
