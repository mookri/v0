<?php

namespace Tests\Modules\Onboarding;

use App\Models\User;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Tenancy\Models\Tenant;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

final class OnboardingMsg91FlowTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
    }

    private function startApplication(Tenant $tenant, User $user, array $evidence): string
    {
        $response = $this->actingAs($user)->postJson(
            '/api/v1/onboarding/applications',
            ['track' => 'pandit'],
            $this->tenantHeaders($tenant)
        );
        $response->assertCreated();
        $id = $response->json('data.id');

        $app = PartnerApplication::withoutGlobalScope(\App\Platform\Tenancy\TenantScope::class)->findOrFail($id);
        $app->forceFill(['evidence' => $evidence])->save();

        return $id;
    }

    private function validEvidence(string $phone = '+919876543210'): array
    {
        return [
            'identity' => ['name' => 'Ravi Pandit', 'phone' => $phone],
            'credentials' => ['certifications' => ['vedic']],
            'services' => ['lakshmi-puja'],
            'areas' => ['delhi'],
            'availability' => ['set' => true],
            'documents' => ['doc_1'],
            'agreement' => ['accepted' => true],
        ];
    }

    public function test_begin_returns_422_without_contact_phone(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        // Missing phone in evidence
        $evidence = $this->validEvidence();
        unset($evidence['identity']['phone']);
        $id = $this->startApplication($tenant, $user, $evidence);

        $res = $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit",
            [],
            $this->tenantHeaders($tenant)
        );

        $res->assertStatus(422);
    }

    public function test_begin_with_contact_phone_and_resend_and_confirm(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $id = $this->startApplication($tenant, $user, $this->validEvidence('+919876543210'));

        // 1. Begin submit -> creates challenge
        $beginRes = $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit",
            [],
            $this->tenantHeaders($tenant)
        );
        $beginRes->assertOk();
        $challengeId = $beginRes->json('data.challenge_id');
        $this->assertNotEmpty($challengeId);

        // 2. Immediate resend is throttled / rate limited (60s schedule) -> 429
        $resendRes = $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit/resend",
            ['challenge_id' => $challengeId],
            $this->tenantHeaders($tenant)
        );
        $resendRes->assertStatus(429);
        $this->assertNotEmpty($resendRes->headers->get('Retry-After'));

        // 3. Confirm with matching MSG91 token
        $token = $this->msg91TokenFor('+919876543210');
        $confirmRes = $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit/confirm",
            ['challenge_id' => $challengeId, 'token' => $token],
            $this->tenantHeaders($tenant)
        );
        $confirmRes->assertOk();
        $confirmRes->assertJsonPath('data.status', 'SUBMITTED');
    }
}
