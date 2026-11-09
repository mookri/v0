<?php

namespace Tests\Modules\Temple;

use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Temple multi-person units and capacity tests (Sub-phase 3.2).
 * Verifies that capacity calculation uses SUM(units) rather than COUNT(*).
 */
final class TempleCapacityUnitsTest extends TenantIntegrationTestCase
{
    public function test_temple_slot_sum_capacity_allows_multiple_bookings_until_limit(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $templeId = '01JQZZZZZZZZZZZZZZZZZZZZ88';
        $manager = new ResourceAllocationManager();

        $start = CarbonImmutable::parse('2026-11-20 09:00:00');
        $end = CarbonImmutable::parse('2026-11-20 10:00:00');
        $expiresAt = CarbonImmutable::now()->addMinutes(15);
        $capacity = 5;

        $this->resolveContext($tenant);
        try {
            // 1. Booking A for 2 persons (units = 2)
            $alloc1 = $manager->holdTempleSlot(
                $tenantId,
                $templeId,
                'booking-darshan-1',
                'rev-1',
                $start,
                $end,
                $capacity,
                $expiresAt,
                2
            );
            $this->assertSame(2, $alloc1->units);

            // 2. Booking B for 3 persons (units = 3) -> cumulative 5 / 5
            $alloc2 = $manager->holdTempleSlot(
                $tenantId,
                $templeId,
                'booking-darshan-2',
                'rev-2',
                $start,
                $end,
                $capacity,
                $expiresAt,
                3
            );
            $this->assertSame(3, $alloc2->units);

            // 3. Booking C for 1 person (units = 1) -> 5 + 1 = 6 > 5: MUST FAIL
            $this->expectException(ResourceUnavailableException::class);
            $this->expectExceptionMessage("Temple venue capacity limit ({$capacity}) reached");

            $manager->holdTempleSlot(
                $tenantId,
                $templeId,
                'booking-darshan-3',
                'rev-3',
                $start,
                $end,
                $capacity,
                $expiresAt,
                1
            );
        } finally {
            $this->clearContext();
        }
    }

    public function test_expired_held_units_are_excluded_from_sum(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $templeId = '01JQZZZZZZZZZZZZZZZZZZZZ89';
        $manager = new ResourceAllocationManager();

        $start = CarbonImmutable::parse('2026-11-20 09:00:00');
        $end = CarbonImmutable::parse('2026-11-20 10:00:00');
        $capacity = 5;

        $this->resolveContext($tenant);
        try {
            // Seed expired held allocation of 4 units
            $alloc = $manager->holdTempleSlot(
                $tenantId,
                $templeId,
                'booking-expired',
                'rev-exp',
                $start,
                $end,
                $capacity,
                CarbonImmutable::now()->subMinutes(5),
                4
            );

            // Candidate booking requests 3 units. Since previous 4 units are expired,
            // active count is 0, so 3 units must succeed.
            $fresh = $manager->holdTempleSlot(
                $tenantId,
                $templeId,
                'booking-fresh',
                'rev-fresh',
                $start,
                $end,
                $capacity,
                CarbonImmutable::now()->addMinutes(15),
                3
            );

            $this->assertSame(3, $fresh->units);
        } finally {
            $this->clearContext();
        }
    }

    public function test_committed_units_remain_counted(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $templeId = '01JQZZZZZZZZZZZZZZZZZZZZ90';
        $manager = new ResourceAllocationManager();

        $start = CarbonImmutable::parse('2026-11-20 09:00:00');
        $end = CarbonImmutable::parse('2026-11-20 10:00:00');
        $capacity = 5;

        $this->resolveContext($tenant);
        try {
            $held = $manager->holdTempleSlot(
                $tenantId,
                $templeId,
                'booking-commit-me',
                'rev-1',
                $start,
                $end,
                $capacity,
                CarbonImmutable::now()->addMinutes(15),
                3
            );

            $manager->commitAllocations($tenantId, 'booking-commit-me');

            // Now 3 units are COMMITTED. Requesting 3 more units (3 + 3 = 6 > 5) must fail.
            $this->expectException(ResourceUnavailableException::class);
            $manager->holdTempleSlot(
                $tenantId,
                $templeId,
                'booking-overflow',
                'rev-2',
                $start,
                $end,
                $capacity,
                CarbonImmutable::now()->addMinutes(15),
                3
            );
        } finally {
            $this->clearContext();
        }
    }
}
