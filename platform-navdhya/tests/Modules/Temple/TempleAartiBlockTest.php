<?php

namespace Tests\Modules\Temple;

use App\Models\User;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Temple\Contracts\TempleSchedulePolicy;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TempleAartiSchedule;
use App\Modules\Temple\Models\TempleSanctumRule;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Temple Aarti Block and Sanctum Exclusive Policy tests (Sub-phase 3.4, C4).
 */
final class TempleAartiBlockTest extends TenantIntegrationTestCase
{
    private function seedTemple(Tenant $tenant): Temple
    {
        $this->resolveContext($tenant);
        try {
            $user = User::factory()->create();
            $this->makeMembership($tenant, $user);

            return Temple::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'owner_id' => $user->getAuthIdentifier(),
                'status' => Temple::STATUS_ACTIVE,
                'verification_status' => Temple::VERIFICATION_VERIFIED,
                'timezone' => 'Asia/Kolkata',
            ]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_homa_ritual_blocked_during_aarti_hours(): void
    {
        $tenant = $this->makeTenant();
        $temple = $this->seedTemple($tenant);

        // Monday 2026-11-16 (day 1)
        $this->resolveContext($tenant);
        try {
            TempleAartiSchedule::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'day_of_week' => 1,
                'start_time' => '18:00:00',
                'end_time' => '19:30:00',
                'blocks_homa' => true,
                'blocks_darshan' => true,
            ]);

            /** @var TempleSchedulePolicy $policy */
            $policy = app(TempleSchedulePolicy::class);

            // Attempt HOMA ritual 18:30 - 19:15 IST
            $start = CarbonImmutable::parse('2026-11-16 18:30:00', 'Asia/Kolkata');
            $end = CarbonImmutable::parse('2026-11-16 19:15:00', 'Asia/Kolkata');

            $verdict = $policy->evaluateSlot(
                (string) $tenant->getKey(),
                (string) $temple->getKey(),
                'HOMA',
                $start,
                $end,
                1
            );

            $this->assertFalse($verdict->isAllowed());
            $this->assertSame('aarti_homa_blocked', $verdict->reason());
        } finally {
            $this->clearContext();
        }
    }

    public function test_darshan_blocked_during_darshan_blocking_aarti(): void
    {
        $tenant = $this->makeTenant();
        $temple = $this->seedTemple($tenant);

        $this->resolveContext($tenant);
        try {
            TempleAartiSchedule::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'day_of_week' => 1,
                'start_time' => '07:00:00',
                'end_time' => '08:00:00',
                'blocks_darshan' => true,
                'blocks_homa' => false,
            ]);

            /** @var TempleSchedulePolicy $policy */
            $policy = app(TempleSchedulePolicy::class);

            $start = CarbonImmutable::parse('2026-11-16 07:15:00', 'Asia/Kolkata');
            $end = CarbonImmutable::parse('2026-11-16 07:45:00', 'Asia/Kolkata');

            $verdict = $policy->evaluateSlot(
                (string) $tenant->getKey(),
                (string) $temple->getKey(),
                'DARSHAN',
                $start,
                $end,
                1
            );

            $this->assertFalse($verdict->isAllowed());
            $this->assertSame('aarti_darshan_blocked', $verdict->reason());
        } finally {
            $this->clearContext();
        }
    }

    public function test_ritual_allowed_outside_aarti_hours(): void
    {
        $tenant = $this->makeTenant();
        $temple = $this->seedTemple($tenant);

        $this->resolveContext($tenant);
        try {
            TempleAartiSchedule::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'day_of_week' => 1,
                'start_time' => '18:00:00',
                'end_time' => '19:30:00',
                'blocks_homa' => true,
                'blocks_darshan' => true,
            ]);

            /** @var TempleSchedulePolicy $policy */
            $policy = app(TempleSchedulePolicy::class);

            // Morning slot 10:00 - 11:30 IST
            $start = CarbonImmutable::parse('2026-11-16 10:00:00', 'Asia/Kolkata');
            $end = CarbonImmutable::parse('2026-11-16 11:30:00', 'Asia/Kolkata');

            $verdict = $policy->evaluateSlot(
                (string) $tenant->getKey(),
                (string) $temple->getKey(),
                'HOMA',
                $start,
                $end,
                1
            );

            $this->assertTrue($verdict->isAllowed());
        } finally {
            $this->clearContext();
        }
    }

    public function test_sanctum_exclusive_occupied_blocks_concurrent_events(): void
    {
        $tenant = $this->makeTenant();
        $temple = $this->seedTemple($tenant);
        $manager = new ResourceAllocationManager();

        $this->resolveContext($tenant);
        try {
            // Seed exclusive sanctum rule (allows_multiple_events = false)
            TempleSanctumRule::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'max_occupancy_units' => 20,
                'allows_multiple_events' => false,
            ]);

            $start = CarbonImmutable::parse('2026-11-16 10:00:00', 'Asia/Kolkata');
            $end = CarbonImmutable::parse('2026-11-16 11:30:00', 'Asia/Kolkata');

            // Hold an existing slot
            $manager->holdTempleSlot(
                (string) $tenant->getKey(),
                (string) $temple->getKey(),
                'existing-booking',
                'rev-1',
                $start->utc(),
                $end->utc(),
                20,
                CarbonImmutable::now()->addMinutes(15),
                1
            );

            /** @var TempleSchedulePolicy $policy */
            $policy = app(TempleSchedulePolicy::class);

            // Overlapping slot 10:30 - 12:00 IST
            $candStart = CarbonImmutable::parse('2026-11-16 10:30:00', 'Asia/Kolkata');
            $candEnd = CarbonImmutable::parse('2026-11-16 12:00:00', 'Asia/Kolkata');

            $verdict = $policy->evaluateSlot(
                (string) $tenant->getKey(),
                (string) $temple->getKey(),
                'PUJA',
                $candStart,
                $candEnd,
                1
            );

            $this->assertFalse($verdict->isAllowed());
            $this->assertSame('sanctum_exclusive_event_occupied', $verdict->reason());
        } finally {
            $this->clearContext();
        }
    }
}
