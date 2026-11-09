<?php

namespace App\Modules\Temple\Application;

use App\Modules\Booking\Models\ResourceAllocation;
use App\Modules\Booking\Models\ResourceCalendar;
use App\Modules\Temple\Contracts\TempleSchedulePolicy;
use App\Modules\Temple\Contracts\TempleScheduleVerdict;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TempleAartiSchedule;
use App\Modules\Temple\Models\TempleSanctumRule;
use App\Modules\Temple\Models\TempleVenueLimit;
use Carbon\CarbonImmutable;

/**
 * Authoritative Temple Schedule Policy Service.
 * Evaluates Aarti collisions, sanctum limits, and multi-constraint capacity bounds.
 */
final class TempleSchedulePolicyService implements TempleSchedulePolicy
{
    public function evaluateSlot(
        string $tenantId,
        string $templeId,
        string $slotType,
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $requestedUnits = 1
    ): TempleScheduleVerdict {
        $temple = Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->first();

        if ($temple === null) {
            return TempleScheduleVerdict::rejected('temple_not_found');
        }

        if ($temple->verification_status !== Temple::VERIFICATION_VERIFIED || $temple->status !== Temple::STATUS_ACTIVE) {
            return TempleScheduleVerdict::rejected('temple_inactive_or_unverified');
        }

        $tz = $temple->timezone ?? 'Asia/Kolkata';
        $localStart = $start->setTimezone($tz);
        $localEnd = $end->setTimezone($tz);
        $dayOfWeek = (int) $localStart->dayOfWeek;

        // 1. Check Aarti schedules overlap
        $aartis = TempleAartiSchedule::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('day_of_week', $dayOfWeek)
            ->get();

        $startTimeStr = $localStart->format('H:i:s');
        $endTimeStr = $localEnd->format('H:i:s');

        foreach ($aartis as $aarti) {
            $aartiStart = $aarti->start_time->format('H:i:s');
            $aartiEnd = $aarti->end_time->format('H:i:s');

            // Check if intervals overlap: aartiStart < endTimeStr && startTimeStr < aartiEnd
            if ($aartiStart < $endTimeStr && $startTimeStr < $aartiEnd) {
                if ($slotType === 'DARSHAN' && $aarti->blocks_darshan) {
                    return TempleScheduleVerdict::rejected('aarti_darshan_blocked', [
                        'aarti_id' => (string) $aarti->getKey(),
                        'start' => $aartiStart,
                        'end' => $aartiEnd,
                    ]);
                }

                if (in_array($slotType, ['HOMA', 'PUJA'], true) && $aarti->blocks_homa) {
                    return TempleScheduleVerdict::rejected('aarti_homa_blocked', [
                        'aarti_id' => (string) $aarti->getKey(),
                        'start' => $aartiStart,
                        'end' => $aartiEnd,
                    ]);
                }
            }
        }

        // 2. Sanctum Rules
        $sanctumRule = TempleSanctumRule::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->first();

        $maxSanctumUnits = $sanctumRule !== null ? (int) $sanctumRule->max_occupancy_units : 100;

        // 3. Resolve active units currently committed or held for this slot
        $calendar = ResourceCalendar::query()
            ->where('tenant_id', $tenantId)
            ->where('resource_type', ResourceCalendar::TYPE_TEMPLE)
            ->where('resource_id', $templeId)
            ->first();

        $activeUnits = 0;
        if ($calendar !== null) {
            $now = now();
            $allocationsQuery = ResourceAllocation::query()
                ->where('tenant_id', $tenantId)
                ->where('calendar_id', (string) $calendar->getKey())
                ->where(function ($query) use ($now): void {
                    $query->where('status', ResourceAllocation::STATUS_COMMITTED)
                        ->orWhere(function ($heldQuery) use ($now): void {
                            $heldQuery->where('status', ResourceAllocation::STATUS_HELD)
                                ->where('expires_at', '>', $now);
                        });
                })
                ->where('interval_start', '<', $end->utc()->toDateTimeString())
                ->where('interval_end', '>', $start->utc()->toDateTimeString());

            if ($sanctumRule !== null && ! $sanctumRule->allows_multiple_events) {
                if ($allocationsQuery->exists()) {
                    return TempleScheduleVerdict::rejected('sanctum_exclusive_event_occupied');
                }
            }

            $activeUnits = (int) $allocationsQuery->sum('units');
        }

        // 4. Venue Limits
        $venueLimits = TempleVenueLimit::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->get();

        $minCapacity = $maxSanctumUnits;
        foreach ($venueLimits as $limit) {
            if ($limit->scope === TempleVenueLimit::SCOPE_CONCURRENT_EVENTS || ($limit->scope === TempleVenueLimit::SCOPE_SLOT_TYPE && $limit->slot_type === $slotType)) {
                $minCapacity = min($minCapacity, (int) $limit->limit_units);
            }
        }

        $availableCapacity = max(0, $minCapacity - $activeUnits);
        if ($requestedUnits > $availableCapacity) {
            return TempleScheduleVerdict::rejected('insufficient_capacity', [
                'available' => $availableCapacity,
                'requested' => $requestedUnits,
            ]);
        }

        return TempleScheduleVerdict::allowed($availableCapacity);
    }
}
