<?php

namespace Tests\Modules\Onboarding;

use App\Models\User;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Modules\Onboarding\OtpCodeGenerator;
use App\Modules\Onboarding\OtpService;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

final class FakeOtpCodes implements OtpCodeGenerator
{
    /** @var array<string, string> */
    public array $issued = [];

    private int $seq = 0;

    public function generate(int $length): string
    {
        $this->seq++;

        return str_pad((string) (100000 + ($this->seq % 900000)), $length, '0', STR_PAD_LEFT);
    }

    public function record(string $challengeId, string $code): void
    {
        $this->issued[$challengeId] = $code;
    }

    public function lastFor(string $challengeId): ?string
    {
        return $this->issued[$challengeId] ?? null;
    }
}

/**
 * Two-phase submit over HTTP: incomplete blocks, OTP confirms, replay and
 * exhaustion collapse to one error, rate limits hold.
 */
final class OnboardingSubmitTest extends TenantIntegrationTestCase
{
    private FakeOtpCodes $codes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->codes = new FakeOtpCodes();
        $codes = $this->codes;
        $this->app->singleton(OtpService::class, static fn (): OtpService => new OtpService($codes));
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
            'identity' => ['name' => 'Ravi', 'phone' => '+91'],
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

        $confirm = fn (string $code) => $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit/confirm",
            ['challenge_id' => $challengeId, 'code' => $code],
            $this->tenantHeaders($tenant)
        );

        $confirm('000000')->assertStatus(401);
        $confirm($this->codes->lastFor($challengeId))->assertOk()->assertJsonPath('data.status', 'SUBMITTED');
        $confirm($this->codes->lastFor($challengeId))->assertStatus(409);
    }

    public function test_rate_limit_blocks_challenge_flood(): void
    {
        [$tenant, $user, $id] = $this->startPanditApp();
        $this->fillEvidence($tenant, $user, $id, $this->fullEvidence());

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson(
                "/api/v1/onboarding/applications/{$id}/submit",
                [],
                $this->tenantHeaders($tenant)
            )->assertOk();
        }
        $this->actingAs($user)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit",
            [],
            $this->tenantHeaders($tenant)
        )->assertStatus(429);
    }
}
