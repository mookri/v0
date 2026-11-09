<?php

namespace Tests\Modules\Booking;

use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Booking\Models\ResourceAllocation;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Resource Allocation & Concurrency Tests (Phase 2, Task 2.2).
 */
final class ResourceAllocationManagerTest extends TenantIntegrationTestCase
{
    private ResourceAllocationManager $manager;
    private string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ResourceAllocationManager();
        $tenant = $this->makeTenant();
        $this->tenantId = (string) $tenant->getKey();
    }

    public function test_hold_pandit_slot_creates_held_allocation_and_blocks_overlapping_hold(): void
    {
        $start = CarbonImmutable::parse('2030-06-01T10:00:00Z');
        $end = CarbonImmutable::parse('2030-06-01T11:00:00Z');
        $expires = CarbonImmutable::now()->addMinutes(15);

        $alloc1 = $this->manager->holdPanditSlot(
            $this->tenantId,
            'pandit-alpha',
            'booking-1',
            null,
            $start,
            $end,
            $expires
        );

        $this->assertSame(ResourceAllocation::STATUS_HELD, $alloc1->status);
        $this->assertSame('booking-1', $alloc1->booking_id);

        // Competing customer attempting overlapping slot [10:30, 11:30)
        $competingStart = CarbonImmutable::parse('2030-06-01T10:30:00Z');
        $competingEnd = CarbonImmutable::parse('2030-06-01T11:30:00Z');

        $this->expectException(ResourceUnavailableException::class);
        $this->expectExceptionMessage("Pandit 'pandit-alpha' is not available for the requested slot.");

        $this->manager->holdPanditSlot(
            $this->tenantId,
            'pandit-alpha',
            'booking-2',
            null,
            $competingStart,
            $competingEnd,
            $expires
        );
    }

    public function test_adjacent_half_open_intervals_do_not_conflict(): void
    {
        $slot1Start = CarbonImmutable::parse('2030-06-01T10:00:00Z');
        $slot1End = CarbonImmutable::parse('2030-06-01T11:00:00Z');
        $expires = CarbonImmutable::now()->addMinutes(15);

        $alloc1 = $this->manager->holdPanditSlot(
            $this->tenantId,
            'pandit-beta',
            'booking-1',
            null,
            $slot1Start,
            $slot1End,
            $expires
        );

        // Slot 2 begins exactly at Slot 1's end: [11:00, 12:00)
        $slot2Start = CarbonImmutable::parse('2030-06-01T11:00:00Z');
        $slot2End = CarbonImmutable::parse('2030-06-01T12:00:00Z');

        $alloc2 = $this->manager->holdPanditSlot(
            $this->tenantId,
            'pandit-beta',
            'booking-2',
            null,
            $slot2Start,
            $slot2End,
            $expires
        );

        $this->assertSame(ResourceAllocation::STATUS_HELD, $alloc1->status);
        $this->assertSame(ResourceAllocation::STATUS_HELD, $alloc2->status);
    }

    public function test_temple_capacity_allows_up_to_limit_and_blocks_excess(): void
    {
        $start = CarbonImmutable::parse('2030-06-01T10:00:00Z');
        $end = CarbonImmutable::parse('2030-06-01T11:00:00Z');
        $expires = CarbonImmutable::now()->addMinutes(15);
        $capacityLimit = 2;

        $alloc1 = $this->manager->holdTempleSlot(
            $this->tenantId,
            'temple-somnath',
            'booking-1',
            null,
            $start,
            $end,
            $capacityLimit,
            $expires
        );

        $alloc2 = $this->manager->holdTempleSlot(
            $this->tenantId,
            'temple-somnath',
            'booking-2',
            null,
            $start,
            $end,
            $capacityLimit,
            $expires
        );

        $this->assertNotNull($alloc1);
        $this->assertNotNull($alloc2);

        // Third booking exceeds capacity
        $this->expectException(ResourceUnavailableException::class);
        $this->expectExceptionMessage('Temple venue capacity limit (2) reached');

        $this->manager->holdTempleSlot(
            $this->tenantId,
            'temple-somnath',
            'booking-3',
            null,
            $start,
            $end,
            $capacityLimit,
            $expires
        );
    }

    public function test_commit_and_release_lifecycle(): void
    {
        $start = CarbonImmutable::parse('2030-06-01T10:00:00Z');
        $end = CarbonImmutable::parse('2030-06-01T11:00:00Z');
        $expires = CarbonImmutable::now()->addMinutes(15);

        $alloc = $this->manager->holdPanditSlot(
            $this->tenantId,
            'pandit-gamma',
            'booking-commit',
            null,
            $start,
            $end,
            $expires
        );

        $this->assertSame(ResourceAllocation::STATUS_HELD, $alloc->status);

        $committed = $this->manager->commitAllocations($this->tenantId, 'booking-commit');
        $this->assertSame(1, $committed);
        $alloc->refresh();
        $this->assertSame(ResourceAllocation::STATUS_COMMITTED, $alloc->status);
        $this->assertNull($alloc->expires_at);

        $released = $this->manager->releaseAllocations($this->tenantId, 'booking-commit', 'USER_CANCELLED');
        $this->assertSame(1, $released);
        $alloc->refresh();
        $this->assertSame(ResourceAllocation::STATUS_RELEASED, $alloc->status);
        $this->assertSame('USER_CANCELLED', $alloc->release_reason);
    }

    public function test_reap_expired_holds_marks_timed_out_allocations(): void
    {
        $start = CarbonImmutable::parse('2030-06-01T10:00:00Z');
        $end = CarbonImmutable::parse('2030-06-01T11:00:00Z');
        $pastExpires = CarbonImmutable::now()->subMinutes(5);

        $alloc = $this->manager->holdPanditSlot(
            $this->tenantId,
            'pandit-delta',
            'booking-expired',
            null,
            $start,
            $end,
            $pastExpires
        );

        $reaped = $this->manager->reapExpiredHolds($this->tenantId);
        $this->assertSame(1, $reaped);

        $alloc->refresh();
        $this->assertSame(ResourceAllocation::STATUS_EXPIRED, $alloc->status);
        $this->assertSame('HOLD_TIMEOUT', $alloc->release_reason);
    }
}
