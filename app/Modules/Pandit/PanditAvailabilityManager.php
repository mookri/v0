<?php

namespace App\Modules\Pandit;

use App\Modules\Pandit\Contracts\ManagesPanditAvailability;
use App\Modules\Pandit\Models\PanditAvailability;
use App\Modules\Pandit\Models\PanditProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Pandit availability management (deptrac-blessed Contracts implementation).
 * Ambient tenant context scopes every call.
 */
final class PanditAvailabilityManager implements ManagesPanditAvailability
{
    public function replaceAvailability(string $panditId, array $slots): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        // Verify pandit exists and belongs to tenant
        PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $panditId)
            ->firstOrFail();

        // Fail closed before any write: overlapping slots are never persisted.
        $this->assertNoOverlaps($slots);

        return DB::transaction(function () use ($tenantId, $panditId, $slots) {
            // Delete existing availability for this pandit
            PanditAvailability::query()
                ->where('tenant_id', $tenantId)
                ->where('pandit_id', $panditId)
                ->delete();

            $saved = [];
            foreach ($slots as $slot) {
                $availability = new PanditAvailability();
                $availability->forceFill([
                    'tenant_id' => $tenantId,
                    'pandit_id' => $panditId,
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'slot_duration_minutes' => $slot['slot_duration_minutes'] ?? 60,
                    'is_recurring' => $slot['is_recurring'] ?? true,
                    'specific_date' => $slot['specific_date'] ?? null,
                ]);
                $availability->save();
                $saved[] = $this->toArray($availability);
            }

            return $saved;
        });
    }

    /**
     * @return list<array{id: string, day_of_week: int, start_time: string, end_time: string, slot_duration_minutes: int, is_recurring: bool, specific_date: string|null}>
     */
    public function getAvailability(string $panditId, ?string $startDate = null, ?string $endDate = null): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        $query = PanditAvailability::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId);

        if ($startDate !== null) {
            $start = CarbonImmutable::parse($startDate);
            $query->where(function ($q) use ($start) {
                $q->where('is_recurring', true)
                    ->orWhere('specific_date', '>=', $start->toDateString());
            });
        }

        if ($endDate !== null) {
            $end = CarbonImmutable::parse($endDate);
            $query->where(function ($q) use ($end) {
                $q->where('is_recurring', true)
                    ->orWhere('specific_date', '<=', $end->toDateString());
            });
        }

        return $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn (PanditAvailability $a) => $this->toArray($a))
            ->all();
    }

    /**
     * @return list<array{id: string, day_of_week: int, start_time: string, end_time: string, slot_duration_minutes: int, is_recurring: bool, specific_date: string|null}>
     */
    public function getPublicAvailability(string $panditId, ?string $startDate = null, ?string $endDate = null): array
    {
        // Only return availability for verified, active pandits
        $profile = PanditProfile::query()
            ->where('tenant_id', app(\App\Platform\Tenancy\TenantContext::class)->tenantId())
            ->where('id', $panditId)
            ->where('verification_status', PanditProfile::VERIFICATION_VERIFIED)
            ->where('status', PanditProfile::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->first();

        if ($profile === null) {
            return [];
        }

        return $this->getAvailability($panditId, $startDate, $endDate);
    }

    /**
     * Grouped overlap guard: recurring slots collide per weekday, one-off
     * overrides per specific date. Touching windows (10:00 ends exactly
     * when the next starts) are allowed.
     *
     * @param  list<array{day_of_week: int, start_time: string, end_time: string, slot_duration_minutes?: int, is_recurring?: bool, specific_date?: string|null}>  $slots
     *
     * @throws AvailabilityConflictException when two slots in a group overlap
     */
    private function assertNoOverlaps(array $slots): void
    {
        $groups = [];

        foreach ($slots as $slot) {
            $key = ($slot['is_recurring'] ?? true)
                ? 'd'.(int) $slot['day_of_week']
                : 's'.(string) ($slot['specific_date'] ?? '');

            $groups[$key][] = [
                'start' => CarbonImmutable::parse((string) $slot['start_time']),
                'end' => CarbonImmutable::parse((string) $slot['end_time']),
            ];
        }

        foreach ($groups as $windows) {
            $count = count($windows);

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($windows[$i]['start'] < $windows[$j]['end']
                        && $windows[$j]['start'] < $windows[$i]['end']) {
                        throw new AvailabilityConflictException('Overlapping availability slots detected.');
                    }
                }
            }
        }
    }

    /**
     * @return array{id: string, day_of_week: int, start_time: string, end_time: string, slot_duration_minutes: int, is_recurring: bool, specific_date: string|null}
     */
    private function toArray(PanditAvailability $availability): array
    {
        return [
            'id' => (string) $availability->getKey(),
            'day_of_week' => (int) $availability->day_of_week,
            'start_time' => $availability->start_time->format('H:i:s'),
            'end_time' => $availability->end_time->format('H:i:s'),
            'slot_duration_minutes' => (int) $availability->slot_duration_minutes,
            'is_recurring' => (bool) $availability->is_recurring,
            'specific_date' => $availability->specific_date?->toDateString(),
        ];
    }
}
