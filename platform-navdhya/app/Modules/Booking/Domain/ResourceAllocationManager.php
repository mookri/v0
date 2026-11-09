<?php

namespace App\Modules\Booking\Domain;

use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Modules\Booking\Models\ResourceCalendar;
use Carbon\CarbonImmutable;

/**
 * Concurrency & Atomic Resource Allocation Manager (Phase 2, Task 2.2).
 * Locks calendar row and guarantees no double-allocation across half-open intervals.
 */
final class ResourceAllocationManager
{
    public function holdPanditSlot(
        string $tenantId,
        string $panditId,
        string $bookingId,
        ?string $revisionId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $expiresAt
    ): ResourceAllocation {
        $calendar = $this->lockOrCreateCalendar($tenantId, ResourceCalendar::TYPE_PANDIT, $panditId);

        $now = now();
        $hasConflict = ResourceAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('calendar_id', (string) $calendar->getKey())
            ->where(function ($query) use ($now): void {
                $query->where('status', ResourceAllocation::STATUS_COMMITTED)
                    ->orWhere(function ($heldQuery) use ($now): void {
                        $heldQuery->where('status', ResourceAllocation::STATUS_HELD)
                            ->where('expires_at', '>', $now);
                    });
            })
            ->where('interval_start', '<', $end->toDateTimeString())
            ->where('interval_end', '>', $start->toDateTimeString())
            ->where(function ($query) use ($bookingId): void {
                $query->whereNull('booking_id')
                    ->orWhere('booking_id', '!=', $bookingId);
            })
            ->exists();

        if ($hasConflict) {
            throw new ResourceUnavailableException("Pandit '{$panditId}' is not available for the requested slot.");
        }

        $allocation = new ResourceAllocation();
        $allocation->forceFill([
            'tenant_id' => $tenantId,
            'calendar_id' => (string) $calendar->getKey(),
            'booking_id' => $bookingId,
            'revision_id' => $revisionId,
            'interval_start' => $start->toDateTimeString(),
            'interval_end' => $end->toDateTimeString(),
            'units' => 1,
            'status' => ResourceAllocation::STATUS_HELD,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
        $allocation->save();

        return $allocation;
    }

    public function holdTempleSlot(
        string $tenantId,
        string $templeId,
        string $bookingId,
        ?string $revisionId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $capacityLimit,
        CarbonImmutable $expiresAt,
        int $units = 1
    ): ResourceAllocation {
        $calendar = $this->lockOrCreateCalendar($tenantId, ResourceCalendar::TYPE_TEMPLE, $templeId);

        $now = now();
        $activeUnits = (int) ResourceAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('calendar_id', (string) $calendar->getKey())
            ->where(function ($query) use ($now): void {
                $query->where('status', ResourceAllocation::STATUS_COMMITTED)
                    ->orWhere(function ($heldQuery) use ($now): void {
                        $heldQuery->where('status', ResourceAllocation::STATUS_HELD)
                            ->where('expires_at', '>', $now);
                    });
            })
            ->where('interval_start', '<', $end->toDateTimeString())
            ->where('interval_end', '>', $start->toDateTimeString())
            ->where(function ($query) use ($bookingId): void {
                $query->whereNull('booking_id')
                    ->orWhere('booking_id', '!=', $bookingId);
            })
            ->sum('units');

        if (($activeUnits + $units) > $capacityLimit) {
            throw new ResourceUnavailableException("Temple venue capacity limit ({$capacityLimit}) reached for the requested slot.");
        }

        $allocation = new ResourceAllocation();
        $allocation->forceFill([
            'tenant_id' => $tenantId,
            'calendar_id' => (string) $calendar->getKey(),
            'booking_id' => $bookingId,
            'revision_id' => $revisionId,
            'interval_start' => $start->toDateTimeString(),
            'interval_end' => $end->toDateTimeString(),
            'units' => $units,
            'status' => ResourceAllocation::STATUS_HELD,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
        $allocation->save();

        return $allocation;
    }

    public function commitAllocations(string $tenantId, string $bookingId): int
    {
        return ResourceAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->where('status', ResourceAllocation::STATUS_HELD)
            ->update([
                'status' => ResourceAllocation::STATUS_COMMITTED,
                'expires_at' => null,
            ]);
    }

    public function releaseAllocations(string $tenantId, string $bookingId, string $reason): int
    {
        return ResourceAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->whereIn('status', [ResourceAllocation::STATUS_HELD, ResourceAllocation::STATUS_COMMITTED])
            ->update([
                'status' => ResourceAllocation::STATUS_RELEASED,
                'release_reason' => $reason,
            ]);
    }

    public function reapExpiredHolds(string $tenantId): int
    {
        return ResourceAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('status', ResourceAllocation::STATUS_HELD)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => ResourceAllocation::STATUS_EXPIRED,
                'release_reason' => 'HOLD_TIMEOUT',
            ]);
    }

    private function lockOrCreateCalendar(string $tenantId, string $resourceType, string $resourceId): ResourceCalendar
    {
        $calendar = ResourceCalendar::query()
            ->where('tenant_id', $tenantId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->first();

        if ($calendar === null) {
            $calendar = new ResourceCalendar();
            $calendar->forceFill([
                'tenant_id' => $tenantId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'timezone' => 'UTC',
                'version' => 1,
            ]);
            $calendar->save();
        }

        /** @var ResourceCalendar $locked */
        $locked = ResourceCalendar::query()
            ->whereKey((string) $calendar->getKey())
            ->lockForUpdate()
            ->first();

        return $locked;
    }
}
