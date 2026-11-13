<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\PanditArrival;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Primary & corroborating arrival evidence producer (TS-01 / ADR-0060).
 *
 * Uses partner self-attestation (`partner_app`) as primary arrived_at writer,
 * and devotee/coordinator confirmation as secondary corroboration — zero GPS
 * tracking columns.
 */
final class RecordPanditArrivalUseCase
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
        private readonly ?ManagesPanditAssignments $assignments = null,
    ) {
    }

    public function recordPrimary(
        string $bookingId,
        string $actorId,
        string $channel = PanditArrival::SOURCE_PARTNER_APP,
        ?CarbonImmutable $arrivedAt = null,
        ?string $explicitPanditId = null,
    ): PanditArrival {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $timestamp = ($arrivedAt ?? CarbonImmutable::now('UTC'))->utc();

        if (! in_array($channel, [
            PanditArrival::SOURCE_PARTNER_APP,
            PanditArrival::SOURCE_COORDINATOR,
            PanditArrival::SOURCE_OPS_OVERRIDE,
        ], true)) {
            throw new \InvalidArgumentException("Invalid primary arrival source channel: {$channel}");
        }

        $assignment = $this->assignments?->assignmentForBooking($bookingId);
        $panditId = $explicitPanditId ?? ($assignment['pandit_id'] ?? null);
        if ($panditId === null || $panditId === '') {
            throw new BookingStateForbiddenException('No assigned pandit found for arrival check-in.');
        }

        return (new Booking())->getConnection()->transaction(function () use (
            $tenantId,
            $bookingId,
            $actorId,
            $channel,
            $timestamp,
            $assignment,
            $panditId
        ): PanditArrival {
            $booking = Booking::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $bookingId)
                ->lockForUpdate()
                ->first();

            if ($booking === null) {
                throw new BookingNotFoundException('Booking not found.');
            }

            if (! in_array($booking->status, [BookingLifecycle::STATUS_ASSIGNED, BookingLifecycle::STATUS_IN_PROGRESS], true)) {
                throw new BookingStateForbiddenException('Arrival can only be recorded for ASSIGNED or IN_PROGRESS bookings.');
            }

            if ($booking->scheduled_at === null) {
                throw new BookingStateForbiddenException('Booking has no scheduled_at timestamp; delta cannot be computed.');
            }

            $existing = PanditArrival::query()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $bookingId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $scheduled = CarbonImmutable::parse($booking->scheduled_at)->utc();
            $deltaMinutes = (int) $scheduled->diffInMinutes($timestamp, false);

            $arrival = new PanditArrival();
            $arrival->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'assignment_id' => $assignment['id'] ?? null,
                'pandit_id' => $panditId,
                'arrived_at' => $timestamp->toDateTimeString(),
                'recorded_by_actor_id' => $actorId,
                'source_channel' => $channel,
                'dispute_state' => PanditArrival::DISPUTE_NONE,
                'delta_minutes' => $deltaMinutes,
            ]);
            $arrival->save();

            $this->audit->record(
                'booking.pandit_arrival.recorded',
                ['module' => 'booking', 'resource_type' => 'pandit_arrival', 'resource_id' => (string) $arrival->getKey()],
                null,
                [
                    'booking_id' => $bookingId,
                    'pandit_id' => $panditId,
                    'source_channel' => $channel,
                    'delta_minutes' => $deltaMinutes,
                ]
            );

            $this->outbox->append(
                $tenantId,
                'booking',
                $bookingId,
                'booking.pandit_arrival.recorded',
                [
                    'booking_id' => $bookingId,
                    'pandit_id' => $panditId,
                    'arrived_at' => $timestamp->toJSON(),
                    'scheduled_at' => $scheduled->toJSON(),
                    'delta_minutes' => $deltaMinutes,
                    'source_channel' => $channel,
                ]
            );

            return $arrival;
        });
    }

    public function corroborate(
        string $bookingId,
        string $corroboratorId,
        string $channel = PanditArrival::SOURCE_DEVOTEE_CORROBORATION,
    ): PanditArrival {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        return (new Booking())->getConnection()->transaction(function () use ($tenantId, $bookingId, $corroboratorId, $channel, $now): PanditArrival {
            $arrival = PanditArrival::query()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $bookingId)
                ->lockForUpdate()
                ->first();

            if ($arrival === null) {
                throw new BookingNotFoundException('Primary arrival record does not exist to corroborate.');
            }

            if ($arrival->corroborated_at !== null) {
                return $arrival;
            }

            $arrival->forceFill([
                'corroborated_at' => $now->toDateTimeString(),
                'corroborated_by_id' => $corroboratorId,
                'corroboration_channel' => $channel,
            ]);
            $arrival->save();

            $this->audit->record(
                'booking.pandit_arrival.corroborated',
                ['module' => 'booking', 'resource_type' => 'pandit_arrival', 'resource_id' => (string) $arrival->getKey()],
                null,
                ['booking_id' => $bookingId, 'corroborated_by_id' => $corroboratorId, 'channel' => $channel]
            );

            return $arrival;
        });
    }
}
