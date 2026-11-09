<?php

namespace Tests\Modules\Temple;

use App\Models\User;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Temple puja surface (P11g): the three formerly-stubbed endpoints —
 * partner single-assignment show, public pujas, public availability —
 * fail closed around the same VERIFIED + ACTIVE posture as the public
 * directory, expose only ACTIVE assignments, expand schedule windows
 * with optional weekday filtering, and never leak across tenants.
 */
final class TemplePujaSurfaceTest extends TenantIntegrationTestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedTemple(Tenant $tenant, array $overrides = []): Temple
    {
        $temple = Temple::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => Temple::STATUS_ACTIVE,
            'verification_status' => Temple::VERIFICATION_VERIFIED,
            ...$overrides,
        ]);

        if (auth()->check()) {
            \App\Modules\Temple\Models\TempleManager::forceCreate([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'user_id' => (string) auth()->id(),
                'role' => \App\Modules\Temple\Models\TempleManager::ROLE_OWNER,
                'invited_at' => now()->toISOString(),
                'accepted_at' => now()->toISOString(),
            ]);
        }

        return $temple;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedAssignment(Tenant $tenant, Temple $temple, array $overrides = []): TemplePuja
    {
        return TemplePuja::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'temple_id' => (string) $temple->getKey(),
            'status' => TemplePuja::STATUS_ACTIVE,
            ...$overrides,
        ]);
    }

    /**
     * @return array{0: Tenant, 1: array<string, string>}
     */
    private function actingInTenant(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        return [$tenant, $this->tenantHeaders($tenant)];
    }

    public function test_partner_show_returns_single_assignment(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);

        $temple = $this->seedTemple($tenant);
        $assignment = $this->seedAssignment($tenant, $temple, [
            'puja_id' => 'puja_griha_pravesh',
            'price_override_minor' => 15000,
            'capacity' => 12,
        ]);

        $this->getJson(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/pujas/'.$assignment->getKey(),
            $this->tenantHeaders($tenant),
        )
            ->assertOk()
            ->assertJsonPath('data.id', $assignment->getKey())
            ->assertJsonPath('data.temple_id', $temple->getKey())
            ->assertJsonPath('data.puja_id', 'puja_griha_pravesh')
            ->assertJsonPath('data.price_override_minor', 15000)
            ->assertJsonPath('data.capacity', 12)
            ->assertJsonPath('data.status', 'ACTIVE')
            ->assertJsonStructure([
                'data' => ['id', 'temple_id', 'puja_id', 'price_override_minor', 'capacity', 'schedule_json', 'status', 'created_at'],
                'meta' => ['request_id'],
            ]);
    }

    public function test_partner_show_unknown_or_foreign_assignment_is_404(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $temple = $this->seedTemple($tenant);
        $otherTemple = $this->seedTemple($tenant);
        $assignment = $this->seedAssignment($tenant, $temple);

        $this->getJson(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/pujas/01JQZZZZZZZZZZZZZZZZZZZZZZ',
            $headers,
        )
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_PUJA_NOT_FOUND');

        // An existing assignment reached through another temple's path is
        // scoped away exactly like update/remove: one 404, no oracle.
        $this->getJson(
            '/api/v1/temple/partner/temples/'.$otherTemple->getKey().'/pujas/'.$assignment->getKey(),
            $headers,
        )
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_PUJA_NOT_FOUND');
    }

    public function test_public_pujas_are_fail_closed_and_active_only(): void
    {
        [$tenant, $headers] = $this->actingInTenant();

        $verified = $this->seedTemple($tenant);
        $this->seedAssignment($tenant, $verified, ['puja_id' => 'puja_active']);
        $this->seedAssignment($tenant, $verified, [
            'puja_id' => 'puja_paused',
            'status' => TemplePuja::STATUS_INACTIVE,
        ]);

        $pending = $this->seedTemple($tenant, [
            'verification_status' => Temple::VERIFICATION_PENDING,
        ]);
        $this->seedAssignment($tenant, $pending, ['puja_id' => 'puja_hidden']);

        $suspended = $this->seedTemple($tenant, ['status' => Temple::STATUS_INACTIVE]);

        $open = $this->getJson('/api/v1/temple/profiles/'.$verified->getKey().'/pujas', $headers)
            ->assertOk();

        $this->assertCount(1, $open->json('data'));
        $this->assertSame('puja_active', $open->json('data.0.puja_id'));
        $this->assertSame('ACTIVE', $open->json('data.0.status'));
        $this->assertArrayHasKey('page', $open->json('meta'));
        $this->assertArrayHasKey('request_id', $open->json('meta'));

        // Rows exist for the pending temple, but pending, suspended and
        // unknown ids collapse to one 404 — no vetting oracle.
        $this->getJson('/api/v1/temple/profiles/'.$pending->getKey().'/pujas', $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
        $this->getJson('/api/v1/temple/profiles/'.$suspended->getKey().'/pujas', $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
        $this->getJson('/api/v1/temple/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ/pujas', $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
    }

    public function test_verified_temple_without_assignments_lists_empty(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);

        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/pujas', $headers)
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.page.has_more', false)
            ->assertJsonPath('meta.page.next_cursor', null);
    }

    public function test_public_availability_expands_active_schedule_windows(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);

        // Factory schedule: day 1 (Mon) two windows, day 5 (Fri) one.
        $active = $this->seedAssignment($tenant, $temple, [
            'price_override_minor' => 15000,
            'capacity' => 99,
        ]);
        // Same schedule, but paused rows never surface publicly.
        $paused = $this->seedAssignment($tenant, $temple, [
            'puja_id' => 'puja_paused',
            'status' => TemplePuja::STATUS_INACTIVE,
        ]);
        // Window without its own capacity falls back to the assignment's.
        $tuesday = $this->seedAssignment($tenant, $temple, [
            'puja_id' => 'puja_tuesday',
            'price_override_minor' => null,
            'capacity' => 7,
            'schedule_json' => json_encode(['2' => [['start' => '10:00', 'end' => '11:00']]]),
        ]);

        $rows = $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/availability', $headers)
            ->assertOk()
            ->json('data');

        // Three windows from the active factory schedule + the Tuesday one;
        // the paused assignment contributes nothing.
        $this->assertCount(4, $rows);

        $byAssignment = [];
        foreach ($rows as $row) {
            $byAssignment[$row['assignment_id']][] = $row;
        }

        // Active + tuesday groups only; the paused assignment has no rows.
        $this->assertCount(2, $byAssignment);
        $this->assertArrayNotHasKey((string) $paused->getKey(), $byAssignment);

        $activeRows = $byAssignment[(string) $active->getKey()];
        $this->assertCount(3, $activeRows);

        $this->assertSame($active->puja_id, $activeRows[0]['puja_id']);
        $this->assertSame(1, $activeRows[0]['day_of_week']);
        $this->assertSame('06:00', $activeRows[0]['start']);
        $this->assertSame('07:00', $activeRows[0]['end']);
        $this->assertSame(10, $activeRows[0]['capacity']);
        $this->assertSame(15000, $activeRows[0]['price_override_minor']);

        $this->assertSame(1, $activeRows[1]['day_of_week']);
        $this->assertSame('08:00', $activeRows[1]['start']);
        $this->assertSame('09:00', $activeRows[1]['end']);

        $this->assertSame(5, $activeRows[2]['day_of_week']);
        $this->assertSame('18:00', $activeRows[2]['start']);
        $this->assertSame('19:30', $activeRows[2]['end']);
        $this->assertSame(20, $activeRows[2]['capacity']);

        $tuesdayRow = $byAssignment[(string) $tuesday->getKey()][0];
        $this->assertSame('puja_tuesday', $tuesdayRow['puja_id']);
        $this->assertSame(2, $tuesdayRow['day_of_week']);
        $this->assertSame('10:00', $tuesdayRow['start']);
        $this->assertSame('11:00', $tuesdayRow['end']);
        $this->assertSame(7, $tuesdayRow['capacity']);
        $this->assertNull($tuesdayRow['price_override_minor']);
    }

    public function test_public_availability_range_filters_weekdays(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $this->seedAssignment($tenant, $temple);

        $monday = \Carbon\CarbonImmutable::now()->next(\Carbon\CarbonImmutable::MONDAY);
        $base = '/api/v1/temple/profiles/'.$temple->getKey().'/availability';

        // A single Monday keeps only the Monday windows (day 1).
        $mondayOnly = $this->getJson(
            $base.'?start_date='.$monday->toDateString().'&end_date='.$monday->toDateString(),
            $headers,
        )->assertOk()->json('data');

        $this->assertCount(2, $mondayOnly);
        foreach ($mondayOnly as $row) {
            $this->assertSame(1, $row['day_of_week']);
        }

        // A full week (Mon..Sun) covers every weekday, so nothing filters out.
        $week = $this->getJson(
            $base.'?start_date='.$monday->toDateString().'&end_date='.$monday->addDays(6)->toDateString(),
            $headers,
        )->assertOk()->json('data');

        $this->assertCount(3, $week);
    }

    public function test_public_reads_validate_query_parameters(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $this->seedAssignment($tenant, $temple);

        $availability = '/api/v1/temple/profiles/'.$temple->getKey().'/availability';

        $this->getJson($availability.'?start_date=not-a-date', $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
        $this->getJson($availability.'?start_date=2026-10-10&end_date=2026-10-01', $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/pujas?limit=1000', $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_malformed_schedule_is_skipped_not_fatal(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $this->seedAssignment($tenant, $temple, ['schedule_json' => 'not-json-at-all']);

        // The assignment still lists, but contributes no availability rows —
        // bad partner data degrades the payload, never the status.
        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/pujas', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/availability', $headers)
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_puja_surfaces_are_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        // One principal, two memberships: rows never cross the header tenant.
        $user = User::factory()->create();
        $this->makeMembership($tenantA, $user);
        $this->makeMembership($tenantB, $user);
        $this->actingAs($user);

        $temple = $this->seedTemple($tenantA);
        $assignment = $this->seedAssignment($tenantA, $temple);

        $headersB = $this->tenantHeaders($tenantB);

        // Positive control: the same rows resolve under their own tenant.
        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/pujas', $this->tenantHeaders($tenantA))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/pujas', $headersB)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/availability', $headersB)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
        $this->getJson(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/pujas/'.$assignment->getKey(),
            $headersB,
        )
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_PUJA_NOT_FOUND');
    }

    public function test_public_puja_reads_require_a_principal(): void
    {
        $tenant = $this->makeTenant();
        $temple = $this->seedTemple($tenant);
        $this->seedAssignment($tenant, $temple);
        $headers = $this->tenantHeaders($tenant);

        // The kernel binds a tenant only for an authenticated principal:
        // a guest is refused before any route logic runs.
        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/pujas', $headers)
            ->assertStatus(401);
        $this->getJson('/api/v1/temple/profiles/'.$temple->getKey().'/availability', $headers)
            ->assertStatus(401);
    }
}
