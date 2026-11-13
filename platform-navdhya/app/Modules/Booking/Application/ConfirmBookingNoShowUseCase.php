<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Booking\Models\PanditArrival;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Operational NO_SHOW writer (TS-02 / ADR-0060).
 *
 * Preconditions (fail-closed):
 *  1. Booking is currently ASSIGNED (transitioning ASSIGNED -> NO_SHOW).
 *  2. No PanditArrival evidence exists for the booking.
 *  3. Scheduled arrival + grace window has passed.
 *  4. Operations human explicitly confirms after outreach (`outreach_attempted` + `reason`).
 *     Never auto-transitions from missing telemetry alone.
 */
final class ConfirmBookingNoShowUseCase
{
    public const DEFAULT_GRACE_MINUTES = 15;

    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    public function execute(
        string $bookingId,
        string $operatorId,
        bool $outreachAttempted,
        string $reason,
        int $graceMinutes = self::DEFAULT_GRACE_MINUTES,
        ?CarbonImmutable $now = null,
    ): Booking {
        if (! $outreachAttempted) {
            throw new \InvalidArgumentException('Outreach attempt must be confirmed before declaring NO_SHOW.');
        }

        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Human confirmation reason is required to declare NO_SHOW.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $clock = ($now ?? CarbonImmutable::now('UTC'))->utc();

        return (new Booking())->getConnection()->transaction(function () use (
            $tenantId,
            $bookingId,
            $operatorId,
            $reason,
            $graceMinutes,
            $clock
        ): Booking {
            $booking = Booking::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $bookingId)
                ->lockForUpdate()
                ->first();

            if ($booking === null) {
                throw new BookingNotFoundException('Booking not found.');
            }

            if ($booking->status === BookingLifecycle::STATUS_NO_SHOW) {
                return $booking;
            }

            if (! BookingLifecycle::canTransition($booking->status, BookingLifecycle::STATUS_NO_SHOW)) {
                throw new BookingStateForbiddenException(
                    sprintf("Cannot transition booking from '%s' to NO_SHOW.", $booking->status)
                );
            }

            if (PanditArrival::query()->where('tenant_id', $tenantId)->where('booking_id', $bookingId)->exists()) {
                throw new BookingStateForbiddenException('Arrival evidence already exists for this booking; NO_SHOW forbidden.');
            }

            if ($booking->scheduled_at !== null) {
                $cutoff = CarbonImmutable::parse($booking->scheduled_at)->utc()->addMinutes($graceMinutes);
                if ($clock->lt($cutoff)) {
                    throw new BookingStateForbiddenException('Grace window has not yet elapsed for NO_SHOW confirmation.');
                }
            }

            $fromStatus = $booking->status;
            $booking->status = BookingLifecycle::STATUS_NO_SHOW;
            $booking->status_detail = 'confirmed_no_show';
            $booking->version = (int) $booking->version + 1;
            $booking->save();

            $transition = new BookingTransition();
            $transition->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'from_status' => $fromStatus,
                'to_status' => BookingLifecycle::STATUS_NO_SHOW,
                'reason' => $reason,
                'actor_id' => $operatorId,
            ]);
            $transition->save();

            $this->audit->record(
                'booking.no_show.confirmed',
                ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => $bookingId],
                ['status' => $fromStatus],
                [
                    'status' => BookingLifecycle::STATUS_NO_SHOW,
                    'operator_id' => $operatorId,
                    'reason' => $reason,
                    'outreach_attempted' => true,
                ]
            );

            $this->outbox->append(
                $tenantId,
                'booking',
                $bookingId,
                'booking.no_show.confirmed',
                [
                    'booking_id' => $bookingId,
                    'user_id' => (string) $booking->user_id,
                    'operator_id' => $operatorId,
                    'reason' => $reason,
                    'confirmed_at' => $clock->toJSON(),
                ]
            );

            return $booking;
        });
    }
}

