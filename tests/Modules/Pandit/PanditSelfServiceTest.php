<?php

namespace Tests\Modules\Pandit;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Pandit self-serve surface (P9c/P9d): apply provisions a PENDING profile,
 * own profile/availability/assignment/earnings/review reads, the strict
 * patch allowlist, state gates, and the ownership/401 matrix.
 */
final class PanditSelfServiceTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function applicant(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        return [$tenant, $user];
    }

    private function seedProfile(Tenant $tenant, User $user): PanditProfile
    {
        $this->resolveContext($tenant);

        try {
            return PanditProfile::factory()->create([
                'user_id' => $user->getAuthIdentifier(),
            ]);
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

    private function seedAssignment(Tenant $tenant, string $panditId, string $bookingId): PanditAssignment
    {
        $this->resolveContext($tenant);

        try {
            return PanditAssignment::factory()->create([
                'pandit_id' => $panditId,
                'booking_id' => $bookingId,
                'status' => PanditAssignment::STATUS_PENDING,
            ]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_apply_provisions_application_and_pending_profile(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);

        $applied = $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)
            ->assertCreated()
            ->assertJsonPath('data.application.track', 'pandit')
            ->assertJsonPath('data.application.track_version', 'pandit-v1')
            ->assertJsonPath('data.application.status', 'DRAFT')
            ->assertJsonPath('data.pandit_profile.verification_status', 'PENDING')
            ->assertJsonPath('data.pandit_profile.status', 'ACTIVE')
            ->assertJsonPath('data.pandit_profile.created', true);

        $this->assertAuditRecorded($tenant, 'pandit.application.started');
        $this->assertAuditRecorded($tenant, 'pandit.profile.provisioned');

        $this->getJson('/api/v1/pandit/application', $headers)
            ->assertOk()->assertJsonPath('data.id', $applied->json('data.application.id'));

        $this->getJson('/api/v1/pandit/profile', $headers)
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'PENDING')
            ->assertJsonPath('data.id', $applied->json('data.pandit_profile.id'));

        // The PENDING profile is invisible to the public directory.
        $this->getJson(
            '/api/v1/pandit/profiles/'.$applied->json('data.pandit_profile.id'),
            $headers,
        )->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
    }

    public function test_apply_guards_track_and_duplicate_live_applications(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);

        $this->postJson('/api/v1/pandit/apply', [], $headers)->assertStatus(422);
        $this->postJson('/api/v1/pandit/apply', ['track' => 'partner-vendor'], $headers)
            ->assertStatus(422);

        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)->assertCreated();

        // Second live application is refused, not silently duplicated.
        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PANDIT_APPLICATION_EXISTS');
    }

    public function test_application_read_is_404_before_applying(): void
    {
        [$tenant] = $this->applicant();

        $this->getJson('/api/v1/pandit/application', $this->tenantHeaders($tenant))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'PANDIT_APPLICATION_NOT_FOUND');
    }

    public function test_own_profile_patch_is_allowlisted(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);

        // Self reads are 404 until the applicant has a profile.
        $this->getJson('/api/v1/pandit/profile', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');

        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)->assertCreated();

        $unknown = $this->patchJson('/api/v1/pandit/profile', ['bio' => 'x', 'role' => 'admin'], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
        $this->assertSame(['unknown_field'], $unknown->json('error.details.fields.role'));

        $updated = $this->patchJson('/api/v1/pandit/profile', [
            'bio' => 'Griha pravesh and satyanarayan puja specialist.',
            'languages' => ['hi', 'en'],
            'experience_years' => 12,
        ], $headers)->assertOk();

        $this->assertSame(
            'Griha pravesh and satyanarayan puja specialist.',
            $updated->json('data.bio'),
        );
        $this->assertSame(12, $updated->json('data.experience_years'));
        $this->assertAuditRecorded($tenant, 'pandit.profile.updated');
    }

    public function test_availability_replace_and_overlap_guard(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);
        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)->assertCreated();

        $slot = fn (string $start, string $end, int $day = 3): array => [[
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'slot_duration_minutes' => 60,
        ]];

        // Overlapping slots inside one replace are refused (fail-closed).
        $this->putJson('/api/v1/pandit/availability', [
            'slots' => [...$slot('09:00:00', '11:00:00'), ...$slot('10:00:00', '12:00:00')],
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'PANDIT_AVAILABILITY_CONFLICT');

        $stored = $this->putJson(
            '/api/v1/pandit/availability',
            ['slots' => $slot('09:00:00', '11:00:00')],
            $headers,
        )->assertOk();
        $this->assertCount(1, $stored->json('data'));
        $this->assertAuditRecorded($tenant, 'pandit.availability.updated');

        $read = $this->getJson('/api/v1/pandit/availability', $headers)->assertOk();
        $this->assertSame('09:00:00', $read->json('data.0.start_time'));
        $this->assertSame(3, $read->json('data.0.day_of_week'));

        // Replace really replaces.
        $this->putJson('/api/v1/pandit/availability', ['slots' => $slot('16:00:00', '18:00:00', 6)], $headers)
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/pandit/availability', $headers)
            ->assertOk()->assertJsonPath('data.0.start_time', '16:00:00');
    }

    public function test_assignment_confirm_decline_state_gates(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);
        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)->assertCreated();

        $panditId = (string) PanditProfile::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', (string) $tenant->getKey())
            ->firstOrFail()
            ->getKey();

        $bookingId = $this->seedBooking($tenant, now()->addDays(5)->toDateTimeString());
        $assignment = $this->seedAssignment($tenant, $panditId, $bookingId);
        $id = (string) $assignment->getKey();

        $listed = $this->getJson('/api/v1/pandit/assignments', $headers)->assertOk();
        $this->assertCount(1, $listed->json('data'));
        $this->assertSame($bookingId, $listed->json('data.0.booking.id'));
        $this->assertSame('PENDING', $listed->json('data.0.status'));

        $this->postJson("/api/v1/pandit/assignments/{$id}:confirm", [], $headers)
            ->assertOk()->assertJsonPath('data.status', 'CONFIRMED');
        $this->assertAuditRecorded($tenant, 'pandit.assignment.confirmed');

        // Confirmed rows are terminal for the self surface.
        $this->postJson("/api/v1/pandit/assignments/{$id}:decline", ['reason' => 'busy'], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'PANDIT_ASSIGNMENT_WRONG_STATE');

        $this->postJson('/api/v1/pandit/assignments/01JQZZZZZZZZZZZZZZZZZZZZZZ:confirm', [], $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_ASSIGNMENT_NOT_FOUND');
    }

    public function test_earnings_and_reviews_start_empty(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);
        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)->assertCreated();

        $earnings = $this->getJson('/api/v1/pandit/earnings', $headers)->assertOk();
        $this->assertSame(0, $earnings->json('data.total_earnings_minor'));
        $this->assertSame(0, $earnings->json('data.completed_assignments_count'));

        $this->getJson('/api/v1/pandit/reviews', $headers)
            ->assertOk()->assertJsonPath('data', []);
    }

    public function test_self_surface_requires_authentication_and_ownership(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);

        $routes = [
            ['get', '/api/v1/pandit/profile'],
            ['get', '/api/v1/pandit/availability'],
            ['get', '/api/v1/pandit/assignments'],
            ['get', '/api/v1/pandit/earnings'],
            ['get', '/api/v1/pandit/reviews'],
            ['get', '/api/v1/pandit/application'],
            ['post', '/api/v1/pandit/apply'],
        ];

        auth()->logout();

        foreach ($routes as [$method, $uri]) {
            $this->json($method, $uri, [], $headers)->assertUnauthorized();
        }

        // A second member of the tenant never sees the first one's rows.
        $other = User::factory()->create();
        $this->makeMembership($tenant, $other);
        $this->actingAs($other);

        $this->getJson('/api/v1/pandit/profile', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
        $this->getJson('/api/v1/pandit/assignments', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
        $this->getJson('/api/v1/pandit/application', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_APPLICATION_NOT_FOUND');
    }

    public function test_decline_requires_reason(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);
        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)->assertCreated();

        $panditId = (string) PanditProfile::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', (string) $tenant->getKey())
            ->firstOrFail()
            ->getKey();
        $bookingId = $this->seedBooking($tenant, now()->addDays(6)->toDateTimeString());
        $id = (string) $this->seedAssignment($tenant, $panditId, $bookingId)->getKey();

        $this->postJson("/api/v1/pandit/assignments/{$id}:decline", [], $headers)->assertStatus(422);

        $this->postJson("/api/v1/pandit/assignments/{$id}:decline", ['reason' => 'Traveling'], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'DECLINED')
            ->assertJsonPath('data.decline_reason', 'Traveling');
        $this->assertAuditRecorded($tenant, 'pandit.assignment.declined');
    }

    public function test_assignments_cursor_advances_to_the_next_page(): void
    {
        [$tenant] = $this->applicant();
        $headers = $this->tenantHeaders($tenant);
        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)->assertCreated();

        $panditId = (string) PanditProfile::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', (string) $tenant->getKey())
            ->firstOrFail()
            ->getKey();

        foreach ([7, 8, 9] as $offset) {
            $bookingId = $this->seedBooking($tenant, now()->addDays($offset)->toDateTimeString());
            $this->seedAssignment($tenant, $panditId, $bookingId);
        }

        $pageOne = $this->getJson('/api/v1/pandit/assignments?limit=2', $headers)->assertOk();
        $this->assertCount(2, $pageOne->json('data'));
        $this->assertTrue($pageOne->json('meta.page.has_more'));
        $cursor = $pageOne->json('meta.page.next_cursor');
        $this->assertIsString($cursor);

        $pageTwo = $this->getJson('/api/v1/pandit/assignments?limit=2&cursor='.urlencode($cursor), $headers)
            ->assertOk();
        $this->assertCount(1, $pageTwo->json('data'));
        $this->assertFalse($pageTwo->json('meta.page.has_more'));
        $this->assertSame(
            [],
            array_values(array_intersect(
                array_column($pageOne->json('data'), 'id'),
                array_column($pageTwo->json('data'), 'id'),
            )),
        );
    }
}
