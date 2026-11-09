<?php

namespace Tests\Modules\Pandit;

use App\Models\User;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditAssignmentRule;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Pandit SLA daemon and rating recompute tests (Sub-phase 2.4).
 */
final class PanditSlaDaemonTest extends TenantIntegrationTestCase
{
    private function seedProfile(Tenant $tenant, array $overrides = []): PanditProfile
    {
        $this->resolveContext($tenant);
        try {
            $user = User::factory()->create();
            $this->makeMembership($tenant, $user);

            return PanditProfile::factory()->create(array_merge([
                'user_id' => $user->getAuthIdentifier(),
                'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
                'status' => PanditProfile::STATUS_ACTIVE,
            ], $overrides));
        } finally {
            $this->clearContext();
        }
    }

    public function test_process_assignment_sla_auto_declines_expired_pending_assignments_and_emits_outbox(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant);

        $this->resolveContext($tenant);
        try {
            // Configure 12 hour SLA
            PanditAssignmentRule::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'response_sla_hours' => 12,
            ]);

            $assignment = PanditAssignment::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'booking_id' => '01JQZZZZZZZZZZZZZZZZZZZZ01',
                'status' => PanditAssignment::STATUS_PENDING,
                'assigned_at' => now(),
            ]);

            // Run SLA sweep immediately — should NOT decline yet
            $this->artisan('pandit:process-assignment-sla', ['--tenant' => (string) $tenant->getKey()])
                ->assertExitCode(0);

            $assignment->refresh();
            $this->assertSame(PanditAssignment::STATUS_PENDING, $assignment->status);

            // Travel 13 hours forward past the SLA deadline
            $this->travel(13)->hours();

            $this->artisan('pandit:process-assignment-sla', ['--tenant' => (string) $tenant->getKey()])
                ->assertExitCode(0);

            $assignment->refresh();
            $this->assertSame(PanditAssignment::STATUS_DECLINED, $assignment->status);
            $this->assertSame('SLA_TIMEOUT', $assignment->decline_reason);
            $this->assertNotNull($assignment->declined_at);

            // Assert outbox message emitted
            $outboxMsg = OutboxEvent::query()
                ->where('event_type', 'pandit.assignment.declined')
                ->where('aggregate_id', (string) $assignment->getKey())
                ->first();

            $this->assertNotNull($outboxMsg);
            $this->assertSame('SLA_TIMEOUT', $outboxMsg->payload['reason'] ?? null);
            $this->assertSame('REASSIGN', $outboxMsg->payload['action_required'] ?? null);
        } finally {
            $this->clearContext();
        }
    }

    public function test_recompute_ratings_updates_profile_stats(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant, [
            'rating_avg' => 0.0,
            'rating_count' => 0,
            'total_bookings' => 0,
        ]);

        $b1 = '01JQZZZZZZZZZZZZZZZZZZZZ11';
        $b2 = '01JQZZZZZZZZZZZZZZZZZZZZ12';

        $bookingsMock = $this->createMock(ReadsBookings::class);
        $bookingsMock->method('reviewsForBookings')->willReturn([
            ['id' => 'rev-1', 'booking_id' => $b1, 'rating' => 5, 'comment' => 'Excellent'],
            ['id' => 'rev-2', 'booking_id' => $b1, 'rating' => 4, 'comment' => 'Very good'],
            ['id' => 'rev-3', 'booking_id' => $b2, 'rating' => 5, 'comment' => 'Superb'],
        ]);
        $this->app->instance(ReadsBookings::class, $bookingsMock);

        $this->resolveContext($tenant);
        try {
            // Seed two completed assignments
            PanditAssignment::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'booking_id' => $b1,
                'status' => PanditAssignment::STATUS_COMPLETED,
            ]);

            PanditAssignment::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'booking_id' => $b2,
                'status' => PanditAssignment::STATUS_COMPLETED,
            ]);

            $this->artisan('pandit:recompute-ratings', [
                '--tenant' => (string) $tenant->getKey(),
                '--pandit' => (string) $profile->getKey(),
            ])->assertExitCode(0);

            $profile->refresh();

            $this->assertSame(2, $profile->total_bookings);
            $this->assertSame(3, $profile->rating_count);
            // (5 + 4 + 5) / 3 = 14 / 3 = 4.666... rounded to 4.7
            $this->assertEquals(4.7, (float) $profile->rating_avg);
        } finally {
            $this->clearContext();
        }
    }
}
