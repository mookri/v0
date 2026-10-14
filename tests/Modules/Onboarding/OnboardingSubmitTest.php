<?php

namespace Tests\Modules\Onboarding;

use App\Models\User;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Tenancy\Models\Tenant;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Two-phase submit over HTTP: incomplete blocks, OTP confirms, replay and
 * exhaustion collapse to one error, rate limits hold.
 */
final class OnboardingSubmitTest extends TenantIntegrationTestCase
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
    private function startPanditApp(): array
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

    /**
     * @param array<string, mixed> $evidence
     */
    private function fillEvidence(Tenant $tenant, User $user, string $id, array $evidence): void
    {
        $app = PartnerApplication::withoutGlobalScope(\App\Platform\Tenancy\TenantScope::class)->findOrFail($id);
        $app->forceFill(['evidence' => $evidence])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function fullEvidence(): array
    {
        return [
            'identity' => ['name' => 'Ravi', 'phone' => '+919876543210'],
            'credentials' => ['certifications' => ['vedic']],
            'services' => ['lakshmi-puja'],
            'areas' => ['delhi'],
            'availability' => ['set' => true],
            'documents' => ['doc_1'],
            'agreement' => ['accepted' => true],
        ];
    }

    public function test_incomplete_draft_blocks_submit(): void
    {
        [$tenant, $user, $id] = $this->startPanditApp();

        $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit",
            [],
            $this->tenantHeaders($tenant)
        )->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_full_submit_confirm_replay_cycle(): void
    {
        [$tenant, $user, $id] = $this->startPanditApp();
        $this->fillEvidence($tenant, $user, $id, $this->fullEvidence());

        $begun = $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit",
            [],
            $this->tenantHeaders($tenant)
        )->assertOk();
        $challengeId = $begun->json('data.challenge_id');
        $this->assertNotEmpty($challengeId);

        $validToken = $this->msg91TokenFor('+919876543210');
        $wrongToken = $this->msg91TokenFor('+919999999999');

        $confirm = fn (string $token) => $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit/confirm",
            ['challenge_id' => $challengeId, 'token' => $token],
            $this->tenantHeaders($tenant)
        );

        // Mismatched token fails (401)
        $confirm($wrongToken)->assertStatus(401);

        // Valid token confirms application
        $confirm($validToken)->assertOk()->assertJsonPath('data.status', 'SUBMITTED');

        // Replay of token fails (already consumed row -> 409 or 401)
        $confirm($validToken)->assertStatus(409);
    }

    public function test_rate_limit_blocks_challenge_flood(): void
    {
        [$tenant, $user, $id] = $this->startPanditApp();
        $this->fillEvidence($tenant, $user, $id, $this->fullEvidence());

        // First issue succeeds
        $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit",
            [],
            $this->tenantHeaders($tenant)
        )->assertOk();

        // Immediate retry is rate-limited (fresh retry gate)
        $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit",
            [],
            $this->tenantHeaders($tenant)
        )->assertStatus(429);
    }
}
