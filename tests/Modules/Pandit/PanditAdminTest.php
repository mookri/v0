<?php

namespace Tests\Modules\Pandit;

use App\Modules\Booking\Models\Booking;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Pandit admin surface (P9e): capability gating on the directory and the
 * assignment dispatcher, step-up gating on the sensitive profile verbs,
 * state gates, cross-module id resolution and the audit trail.
 */
final class PanditAdminTest extends TenantIntegrationTestCase
{
    private function seedProfile(Tenant $tenant, array $overrides = []): PanditProfile
    {
        $this->resolveContext($tenant);

        try {
            return PanditProfile::factory()->create($overrides);
        } finally {
            $this->clearContext();
        }
    }

    private function seedBooking(Tenant $tenant, string $when): string
    {
        $this->resolveContext($tenant);

        try {
            return (string) Booking::factory()->create([
                'scheduled_at' => $when,
                'duration_minutes' => 60,
            ])->getKey();
        } finally {
            $this->clearContext();
        }
    }

    public function test_admin_surfaces_require_capabilities(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $this->getJson('/api/v1/pandit/admin/profiles', $headers)->assertForbidden();
        $this->postJson('/api/v1/pandit/admin/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ:verify', [], $headers)
            ->assertForbidden();
        $this->postJson('/api/v1/pandit/admin/assignments', [], $headers)->assertForbidden();
        $this->deleteJson('/api/v1/pandit/admin/assignments/01JQZZZZZZZZZZZZZZZZZZZZZZ', [], $headers)
            ->assertForbidden();

        // The public directory keeps working for the very same caller.
        $this->getJson('/api/v1/pandit/profiles', $headers)->assertOk();
        $this->assertNotNull($user->id);
    }

    public function test_admin_directory_lists_rows_the_public_one_hides(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['pandit:profile:view']);
        $headers = $this->tenantHeaders($tenant);

        $pending = $this->seedProfile($tenant, [
            'verification_status' => PanditProfile::VERIFICATION_PENDING,
        ]);

        $admin = $this->getJson('/api/v1/pandit/admin/profiles', $headers)->assertOk();
        $this->assertSame(
            [(string) $pending->getKey()],
            array_column($admin->json('data'), 'id'),
        );
        $this->assertArrayHasKey('page', $admin->json('meta'));

        $this->getJson('/api/v1/pandit/profiles/'.(string) $pending->getKey(), $headers)
            ->assertNotFound();

        $filtered = $this->getJson(
            '/api/v1/pandit/admin/profiles?verification_status=VERIFIED',
            $headers,
        )->assertOk();
        $this->assertSame([], $filtered->json('data'));
    }

    public function test_admin_directory_cursor_advances_to_the_next_page(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['pandit:profile:view']);
        $headers = $this->tenantHeaders($tenant);

        $this->seedProfile($tenant);
        $this->seedProfile($tenant);

        $pageOne = $this->getJson('/api/v1/pandit/admin/profiles?limit=1', $headers)->assertOk();
        $this->assertCount(1, $pageOne->json('data'));
        $this->assertTrue($pageOne->json('meta.page.has_more'));
        $cursor = $pageOne->json('meta.page.next_cursor');
        $this->assertIsString($cursor);

        $pageTwo = $this->getJson('/api/v1/pandit/admin/profiles?limit=1&cursor='.urlencode($cursor), $headers)
            ->assertOk();
        $this->assertCount(1, $pageTwo->json('data'));
        $this->assertFalse($pageTwo->json('meta.page.has_more'));
        $this->assertNotSame($pageOne->json('data.0.id'), $pageTwo->json('data.0.id'));
    }

    public function test_verify_suspend_reactivate_are_stepup_gated(): void
    {
        $tenant = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenant, ['pandit:profile:view', 'pandit:profile:manage']);
        $headers = $this->tenantHeaders($tenant);

        $profile = $this->seedProfile($tenant, [
            'verification_status' => PanditProfile::VERIFICATION_PENDING,
        ]);
        $id = (string) $profile->getKey();

        // Sensitive verbs demand a fresh step-up proof.
        $this->postJson("/api/v1/pandit/admin/profiles/{$id}:verify", [], $headers)
            ->assertForbidden()->assertJsonPath('error.code', 'step_up_required');

        $this->stepUp($admin);

        $this->postJson("/api/v1/pandit/admin/profiles/{$id}:verify", [], $headers)
            ->assertOk()->assertJsonPath('data.verification_status', 'VERIFIED');
        $this->assertAuditRecorded($tenant, 'pandit.profile.verified');

        $this->postJson("/api/v1/pandit/admin/profiles/{$id}:verify", [], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'state_forbidden');

        // Suspension removes it from the public directory immediately.
        $this->postJson("/api/v1/pandit/admin/profiles/{$id}:suspend", [], $headers)
            ->assertOk()->assertJsonPath('data.status', 'SUSPENDED');
        $this->assertAuditRecorded($tenant, 'pandit.profile.suspended');
        $this->getJson("/api/v1/pandit/profiles/{$id}", $headers)->assertNotFound();

        $this->postJson("/api/v1/pandit/admin/profiles/{$id}:suspend", [], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'state_forbidden');

        $this->postJson("/api/v1/pandit/admin/profiles/{$id}:reactivate", [], $headers)
            ->assertOk()->assertJsonPath('data.status', 'ACTIVE');
        $this->assertAuditRecorded($tenant, 'pandit.profile.reactivated');
        $this->getJson("/api/v1/pandit/profiles/{$id}", $headers)->assertOk();

        $this->postJson('/api/v1/pandit/admin/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ:verify', [], $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
    }

    public function test_assignment_dispatch_resolves_cross_module_ids(): void
    {
        $tenant = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenant, ['pandit:assignment:manage']);
        $headers = $this->tenantHeaders($tenant);

        $profile = $this->seedProfile($tenant);
        $panditId = (string) $profile->getKey();
        $bookingA = $this->seedBooking($tenant, now()->addDays(9)->setTime(10, 0)->toDateTimeString());
        $bookingB = $this->seedBooking($tenant, now()->addDays(9)->setTime(10, 30)->toDateTimeString());

        $assign = fn (string $pandit, string $booking) => $this->postJson('/api/v1/pandit/admin/assignments', [
            'pandit_id' => $pandit,
            'booking_id' => $booking,
            'compensation_minor' => 75000,
        ], $headers);

        // Cross-module rows are resolved through ports, never `exists:`.
        $assign($panditId, '01JQZZZZZZZZZZZZZZZZZZZZZZ')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.fields.booking_id.0', 'unknown_booking');

        $assign('01JQZZZZZZZZZZZZZZZZZZZZZZ', $bookingA)
            ->assertStatus(422)
            ->assertJsonPath('error.details.fields.pandit_id.0', 'unknown_pandit');

        $created = $assign($panditId, $bookingA)
            ->assertCreated()
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.booking.id', $bookingA)
            ->assertJsonPath('data.compensation_minor', 75000);
        $this->assertAuditRecorded($tenant, 'pandit.assignment.assigned');
        $assignmentId = $created->json('data.id');

        // Replaying the same pair is idempotent (one row, same representation).
        $assign($panditId, $bookingA)->assertCreated()->assertJsonPath('data.id', $assignmentId);
        $this->assertSame(1, PanditAssignment::query()->withoutGlobalScopes()->count());

        // Scheduling conflict: bookingB overlaps bookingA for this pandit.
        $assign($panditId, $bookingB)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PANDIT_ASSIGNMENT_BOOKING_CONFLICT');

        $this->deleteJson("/api/v1/pandit/admin/assignments/{$assignmentId}", [], $headers)
            ->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertAuditRecorded($tenant, 'pandit.assignment.unassigned');

        $this->deleteJson("/api/v1/pandit/admin/assignments/{$assignmentId}", [], $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_ASSIGNMENT_NOT_FOUND');

        // Freed slot accepts the overlapping booking now.
        $assign($panditId, $bookingB)->assertCreated()->assertJsonPath('data.status', 'PENDING');
    }

    public function test_assignment_conflict_ignores_declined_rows(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['pandit:assignment:manage']);
        $headers = $this->tenantHeaders($tenant);

        $profile = $this->seedProfile($tenant);
        $panditId = (string) $profile->getKey();
        $bookingA = $this->seedBooking($tenant, now()->addDays(11)->setTime(9, 0)->toDateTimeString());
        $bookingB = $this->seedBooking($tenant, now()->addDays(11)->setTime(9, 30)->toDateTimeString());

        $this->resolveContext($tenant);

        try {
            PanditAssignment::factory()->create([
                'pandit_id' => $panditId,
                'booking_id' => $bookingA,
                'status' => PanditAssignment::STATUS_DECLINED,
            ]);
        } finally {
            $this->clearContext();
        }

        // A declined assignment no longer holds the window.
        $this->postJson('/api/v1/pandit/admin/assignments', [
            'pandit_id' => $panditId,
            'booking_id' => $bookingB,
            'compensation_minor' => 1000,
        ], $headers)->assertCreated()->assertJsonPath('data.status', 'PENDING');
    }
}
