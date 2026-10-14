<?php

namespace Tests\Modules\Onboarding;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Contract parity (docs/api/parity-onboarding.md): the Laravel module
 * honors the cross-surface contract — identical HTTP statuses for every
 * situation, kernel-registry codes documented in the correspondence table.
 */
final class OnboardingParityTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
    }

    /**
     * @return array{0: Tenant, 1: User, 2: string}
     */
    private function startApp(): array
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $response = $this->actingAs($user)->postJson(
            '/api/v1/onboarding/applications',
            ['track' => 'pandit'],
            $this->tenantHeaders($tenant)
        );
        $response->assertCreated();

        return [$tenant, $user, $response->json('data.id')];
    }

    public function test_status_matrix_matches_parity_table(): void
    {
        // Guest assertions first: actingAs persists across requests.
        $fresh = $this->makeTenant();
        $this->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $this->tenantHeaders($fresh))
            ->assertUnauthorized();
        $this->getJson('/api/v1/onboarding/applications/01J0000000000000000000000', $this->tenantHeaders($fresh))
            ->assertUnauthorized();

        [$tenant, $user, $id] = $this->startApp();
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($user);

        // 404 unknown.
        $authed()->getJson('/api/v1/onboarding/applications/01J0000000000000000000000', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'not_found');

        // 403 foreign owner.
        $other = User::factory()->create();
        $this->makeMembership($tenant, $other);
        $this->actingAs($other)->getJson("/api/v1/onboarding/applications/{$id}", $headers)
            ->assertForbidden()->assertJsonPath('error.code', 'permission_missing');

        // 422 incomplete submit.
        $authed()->postJson("/api/v1/onboarding/applications/{$id}/submit", [], $headers)
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        // 401 bad OTP (seed a challenge via a filled draft first).
        $app = \App\Modules\Onboarding\Models\PartnerApplication::withoutGlobalScope(\App\Platform\Tenancy\TenantScope::class)->findOrFail($id);
        $app->forceFill(['evidence' => [
            'identity' => ['name' => 'Ravi Pandit', 'phone' => '+919876543210'],
            'credentials' => ['certifications' => ['c']],
            'services' => ['s'],
            'areas' => ['a'],
            'availability' => ['set' => true],
            'documents' => ['d'],
            'agreement' => ['accepted' => true],
        ]])->save();
        $begun = $authed()->postJson("/api/v1/onboarding/applications/{$id}/submit", [], $headers)->assertOk();
        $authed()->postJson("/api/v1/onboarding/applications/{$id}/submit/confirm", [
            'challenge_id' => $begun->json('data.challenge_id'),
            'token' => 'invalid.jwt.token',
        ], $headers)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }
}
