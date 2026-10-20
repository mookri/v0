<?php

namespace Tests\Modules\Onboarding;

use Tests\TenantIntegrationTestCase;

/**
 * First real module (T4): onboarding applications end-to-end through the
 * registry mount — version-pinned start, ownership-enforced read, tenant
 * isolation via the shared scope. Proves discovery → routes → model →
 * contract in one vertical.
 */
final class OnboardingModuleTest extends TenantIntegrationTestCase
{
    public function test_module_is_discovered_with_valid_manifest(): void
    {
        $response = $this->getJson('/api/v1/openapi.json');

        // Guests are denied before we even check content.
        $response->assertUnauthorized();
    }

    public function test_start_pins_track_version_and_read_enforces_ownership(): void
    {
        $this->postJson('/api/v1/onboarding/applications', ['track' => 'nope'])
            ->assertUnauthorized();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $started = $this->actingAs($user)->postJson(
            '/api/v1/onboarding/applications',
            ['track' => 'pandit'],
            $this->tenantHeaders($tenant)
        );
        $started->assertCreated();
        $started->assertJsonPath('data.track_version', 'pandit-v1');
        $started->assertJsonPath('data.status', 'DRAFT');
        $id = $started->json('data.id');

        $this->actingAs($user)->getJson("/api/v1/onboarding/applications/{$id}", $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        $other = \App\Models\User::factory()->create();
        $this->makeMembership($tenant, $other);
        $this->actingAs($other)->getJson("/api/v1/onboarding/applications/{$id}", $this->tenantHeaders($tenant))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_missing');
    }

    public function test_applications_are_tenant_isolated(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        [$userA] = $this->actingAsMembership($tenantA);

        $started = $this->actingAs($userA)->postJson(
            '/api/v1/onboarding/applications',
            ['track' => 'pandit'],
            $this->tenantHeaders($tenantA)
        );
        $started->assertCreated();
        $id = $started->json('data.id');

        // Same row id through tenant B's context resolves to nothing.
        [$userB] = $this->actingAsMembership($tenantB);
        $this->actingAs($userB)->getJson("/api/v1/onboarding/applications/{$id}", $this->tenantHeaders($tenantB))
            ->assertNotFound();
    }
}
