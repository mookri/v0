<?php

namespace App\Modules\Pandit\Application;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Pandit\Contracts\EffectiveWindow;
use App\Modules\Pandit\Contracts\PanditSchedulePolicy;
use App\Modules\Pandit\Contracts\ScheduleVerdict;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditAvailability;
use App\Modules\Pandit\Models\PanditBlackout;
use App\Modules\Pandit\Models\PanditProfile;
use Carbon\CarbonImmutable;

/**
 * Authoritative Pandit Schedule Policy service.
 * Enforces travel buffers, blackouts, and recurring working hours.
 */
final class PanditSchedulePolicyService implements PanditSchedulePolicy
{
    public function __construct(
        private readonly ReadsBookings $bookings,
    ) {
    }

    public function getEffectiveWindow(
        string $tenantId,
        string $panditId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): EffectiveWindow {
        $bufferMinutes = (int) (PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $panditId)
            ->value('travel_buffer_minutes') ?? 30);

        return new EffectiveWindow(
            $start,
            $end,
            $bufferMinutes,
            $start->subMinutes($bufferMinutes),
            $end->addMinutes($bufferMinutes)
        );
    }

    public function isAvailableAt(
        string $tenantId,
        string $panditId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): ScheduleVerdict {
        $profile = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $panditId)
            ->first();

        if ($profile === null) {
            return ScheduleVerdict::unavailable('pandit_not_found');
        }

        if ($profile->verification_status !== PanditProfile::VERIFICATION_VERIFIED || $profile->status !== PanditProfile::STATUS_ACTIVE) {
            return ScheduleVerdict::unavailable('pandit_inactive_or_unverified');
        }

        $effectiveWindow = $this->getEffectiveWindow($tenantId, $panditId, $start, $end);

        // 1. Check weekly recurring schedule / availability in pandit's timezone
        $tz = $profile->timezone ?? 'Asia/Kolkata';
        $localStart = $start->setTimezone($tz);
        $localEnd = $end->setTimezone($tz);
        $dayOfWeek = (int) $localStart->dayOfWeek; // 0=Sunday..6=Saturday

        $availabilities = PanditAvailability::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId)
            ->get();

        if ($availabilities->isNotEmpty()) {
            $dateStr = $localStart->toDateString();
            $startTimeStr = $localStart->format('H:i:s');
            $endTimeStr = $localEnd->format('H:i:s');

            $matching = $availabilities->first(function (PanditAvailability $slot) use ($dayOfWeek, $dateStr, $startTimeStr, $endTimeStr): bool {
                $specificDateStr = $slot->specific_date?->toDateString();
                if ($specificDateStr !== null && $specificDateStr !== $dateStr) {
                    return false;
                }
                if ($slot->is_recurring && (int) $slot->day_of_week !== $dayOfWeek) {
                    return false;
                }

                $slotStartStr = $slot->start_time->format('H:i:s');
                $slotEndStr = $slot->end_time->format('H:i:s');

                return $slotStartStr <= $startTimeStr && $slotEndStr >= $endTimeStr;
            });

            if ($matching === null) {
                return ScheduleVerdict::unavailable('outside_working_hours');
            }
        }

        // 2. Check Blackouts (UTC comparison)
        $startUtcStr = $effectiveWindow->bufferedStart->utc()->toDateTimeString();
        $endUtcStr = $effectiveWindow->bufferedEnd->utc()->toDateTimeString();

        $blackout = PanditBlackout::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId)
            ->where('starts_at', '<', $endUtcStr)
            ->where('ends_at', '>', $startUtcStr)
            ->first();

        if ($blackout !== null) {
            return ScheduleVerdict::unavailable('blackout_period', [
                'blackout_id' => (string) $blackout->id,
                'type' => $blackout->type,
                'reason' => $blackout->reason,
            ]);
        }

        // 3. Check existing Assignments (normalized UTC)
        $assignedBookingIds = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId)
            ->whereIn('status', [PanditAssignment::STATUS_PENDING, PanditAssignment::STATUS_CONFIRMED])
            ->pluck('booking_id')
            ->all();

        if (! empty($assignedBookingIds)) {
            $otherWindows = $this->bookings->windowsByIds($assignedBookingIds);
            foreach ($otherWindows as $other) {
                if ($other['scheduled_at'] === null) {
                    continue;
                }

                $otherStart = CarbonImmutable::parse($other['scheduled_at'])->utc();
                $otherEnd = $otherStart->addMinutes($other['duration_minutes']);
                // Pad other booking by travel buffer
                $otherBufferedStart = $otherStart->subMinutes($effectiveWindow->bufferMinutes);
                $otherBufferedEnd = $otherEnd->addMinutes($effectiveWindow->bufferMinutes);

                // Overlap check against candidate buffered window
                if ($otherBufferedStart < $effectiveWindow->bufferedEnd->utc() && $effectiveWindow->bufferedStart->utc() < $otherBufferedEnd) {
                    return ScheduleVerdict::unavailable('assignment_conflict', [
                        'booking_id' => $other['id'],
                    ]);
                }
            }
        }

        return ScheduleVerdict::available();
    }
}
