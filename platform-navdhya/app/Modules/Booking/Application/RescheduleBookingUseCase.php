<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingConflictException;
use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Domain\Exceptions\BookingVersionConflictException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingChange;
use App\Modules\Booking\Models\BookingRevision;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;

final class RescheduleBookingUseCase
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    /**
     * Reschedules an active booking with atomic slot swap and revision tracking.
     *
     * @return array{booking: Booking, revision: BookingRevision, change: BookingChange}
     */
    public function execute(
        string $tenantId,
        string $bookingId,
        int|string $userId,
        CarbonImmutable $newStart,
        int $expectedVersion,
        ?string $reason = null
    ): array {
        /** @var Booking $booking */
        $booking = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        if ($booking === null) {
            throw new BookingNotFoundException('Booking not found.');
        }

        return $booking->getConnection()->transaction(function () use (
            $booking,
            $tenantId,
            $bookingId,
            $userId,
            $newStart,
            $expectedVersion,
            $reason
        ): array {
            /** @var Booking|null $locked */
            $locked = Booking::query()
                ->whereKey((string) $booking->getKey())
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                throw new BookingNotFoundException('Booking not found.');
            }

            if ((int) $expectedVersion !== (int) $locked->version) {
                throw new BookingVersionConflictException('Booking changed since you read it.');
            }

            if (! BookingLifecycle::canReschedule($locked->status)) {
                throw new BookingStateForbiddenException('Cannot reschedule in this state.');
            }

            $durationMinutes = (int) ($locked->duration_minutes ?: 60);
            $newEnd = $newStart->addMinutes($durationMinutes);

            // Check overlap with user's other bookings
            $userOverlap = Booking::query()
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->where('id', '!=', $bookingId)
                ->whereIn('status', [BookingLifecycle::STATUS_CONFIRMED, BookingLifecycle::STATUS_ASSIGNED])
                ->whereNotNull('scheduled_at')
                ->get()
                ->filter(function (Booking $other) use ($newEnd, $newStart): bool {
                    $start = CarbonImmutable::parse($other->scheduled_at);
                    $end = $start->addMinutes($other->duration_minutes);

                    return $start < $newEnd && $newStart < $end;
                })
                ->first();

            if ($userOverlap !== null) {
                throw new BookingConflictException('The new slot overlaps another booking.');
            }

            // Snapshot old values before mutation
            $oldStart = $locked->scheduled_at?->toDateTimeString() ?? '';
            $currentRevVersion = (int) (BookingRevision::query()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $bookingId)
                ->max('version') ?? 0);
            $newRevVersion = $currentRevVersion + 1;

            // Save revision snapshot
            $revision = new BookingRevision();
            $revision->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'version' => $newRevVersion,
                'service_address_json' => is_array($locked->address_snapshot) ? $locked->address_snapshot : null,
                'service_mode' => 'AT_LOCATION',
            ]);
            $revision->save();

            // Update booking
            $locked->scheduled_at = $newStart;
            $locked->current_revision_id = (string) $revision->getKey();
            $locked->version = (int) $locked->version + 1;
            $locked->save();

            // Manage Resource Allocations (Pandit / Temple)
            $existingAllocations = ResourceAllocation::query()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $bookingId)
                ->whereIn('status', [ResourceAllocation::STATUS_HELD, ResourceAllocation::STATUS_COMMITTED])
                ->get();

            // Verify availability for each allocated calendar in the new slot
            foreach ($existingAllocations as $alloc) {
                $conflict = ResourceAllocation::query()
                    ->where('tenant_id', $tenantId)
                    ->where('calendar_id', $alloc->calendar_id)
                    ->where('id', '!=', $alloc->getKey())
                    ->whereIn('status', [ResourceAllocation::STATUS_HELD, ResourceAllocation::STATUS_COMMITTED])
                    ->where(function ($q) use ($newStart, $newEnd) {
                        $q->where('interval_start', '<', $newEnd)
                            ->where('interval_end', '>', $newStart);
                    })
                    ->exists();

                if ($conflict) {
                    throw new BookingConflictException("Resource calendar {$alloc->calendar_id} is unavailable at the new time.");
                }
            }

            // Release old allocations and create new ones for the new window
            foreach ($existingAllocations as $alloc) {
                $status = $alloc->status;
                $alloc->status = ResourceAllocation::STATUS_RELEASED;
                $alloc->release_reason = 'rescheduled';
                $alloc->save();

                ResourceAllocation::query()->create([
                    'tenant_id' => $tenantId,
                    'booking_id' => $bookingId,
                    'calendar_id' => $alloc->calendar_id,
                    'interval_start' => $newStart,
                    'interval_end' => $newEnd,
                    'units' => $alloc->units ?: 1,
                    'status' => $status,
                ]);
            }

            // Record Booking Change
            $change = new BookingChange();
            $change->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'kind' => BookingChange::KIND_RESCHEDULE,
                'proposed_revision_id' => (string) $revision->getKey(),
                'requested_by' => (string) $userId,
                'status' => BookingChange::STATUS_APPLIED,
            ]);
            $change->save();

            // Record transition
            $transition = new BookingTransition();
            $transition->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'from_status' => $locked->status,
                'to_status' => $locked->status,
                'reason' => $reason ?? 'rescheduled',
                'actor_id' => (string) $userId,
            ]);
            $transition->save();

            // Audit
            $this->audit->record(
                'booking.reservation.rescheduled',
                ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => $bookingId],
                ['scheduled_at' => $oldStart, 'version' => $locked->version - 1],
                ['user_id' => (string) $userId, 'scheduled_at' => $newStart->toDateTimeString(), 'revision' => $newRevVersion]
            );

            // Outbox
            $this->outbox->append(
                $tenantId,
                'booking',
                $bookingId,
                'booking.reservation.rescheduled',
                [
                    'booking_id' => $bookingId,
                    'user_id' => (string) $userId,
                    'old_start' => $oldStart,
                    'new_start' => $newStart->toDateTimeString(),
                    'revision_number' => $newRevVersion,
                ]
            );

            return [
                'booking' => $locked,
                'revision' => $revision,
                'change' => $change,
            ];
        });
    }
}
