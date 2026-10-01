<?php

namespace Tests\Modules\Onboarding;

use App\Models\User;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Modules\Onboarding\OtpCodeGenerator;
use App\Modules\Onboarding\OtpService;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantScope;
use Tests\TenantIntegrationTestCase;

final class FakeReviewOtpCodes implements OtpCodeGenerator
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
 * Reviewer surface: capability-gated queue and verb decisions with
 * four-eyes rejections, audit trail, and SLA flags.
 */
final class OnboardingReviewTest extends TenantIntegrationTestCase
{
    /**
     * @param list<string> $reviewerCaps
     * @return array{0: Tenant, 1: User, 2: string}
     */
    private function submittedApp(array $reviewerCaps): array
    {
        $codes = new FakeReviewOtpCodes();
        $this->app->singleton(OtpService::class, static fn (): OtpService => new OtpService($codes));

        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant);
        $started = $this->actingAs($applicant)->postJson(
            '/api/v1/onboarding/applications',
            ['track' => 'pandit'],
            $this->tenantHeaders($tenant)
        );
        $started->assertCreated();
        $id = $started->json('data.id');

        PartnerApplication::withoutGlobalScope(TenantScope::class)->where('id', $id)->update([
            'evidence' => json_encode([
                'identity' => ['name' => 'Ravi', 'phone' => '+91'],
                'credentials' => ['certifications' => ['vedic']],
                'services' => ['lakshmi-puja'],
                'areas' => ['delhi'],
                'availability' => ['set' => true],
                'documents' => ['doc_1'],
                'agreement' => ['accepted' => true],
            ]),
        ]);

        $begun = $this->actingAs($applicant)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit",
            [],
            $this->tenantHeaders($tenant)
        );
        $begun->assertOk();
        $challengeId = $begun->json('data.challenge_id');

        $this->actingAs($applicant)->postJson(
            "/api/v1/onboarding/applications/{$id}/submit/confirm",
            ['challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId)],
            $this->tenantHeaders($tenant)
        )->assertOk();

        $reviewer = User::factory()->create();
        $membership = $this->makeMembership($tenant, $reviewer);
        $this->grantCapabilities($tenant, $membership, $reviewerCaps);

        return [$tenant, $reviewer, $id];
    }

    public function test_queue_requires_reviewer_capability(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->getJson('/api/v1/onboarding/review/queue', $this->tenantHeaders($tenant))
            ->assertForbidden();

        [$tenant2, $reviewer] = (function (): array {
            $t = $this->makeTenant();
            $m = $this->makeMembership($t);
            $u = User::find($m->user_id);
            $this->grantCapabilities($t, $m, ['onboarding:review:view']);

            return [$t, $u];
        })();
        $this->actingAs($reviewer)->getJson('/api/v1/onboarding/review/queue', $this->tenantHeaders($tenant2))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['request_id']]);
    }

    public function test_approve_transitions_and_audits(): void
    {
        [$tenant, $reviewer, $id] = $this->submittedApp(['onboarding:review:view', 'onboarding:review:approve']);

        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/review/{$id}:approve", [], $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonPath('data.status', 'APPROVED');

        $this->assertDatabaseHas('review_records', [
            'application_id' => $id,
            'decision' => 'APPROVED',
        ], 'tenant_shared');
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => (string) $tenant->getKey(),
            'action' => 'onboarding.review.decided',
        ], 'tenant_shared');
    }

    public function test_reject_needs_reason_and_countersign(): void
    {
        [$tenant, $reviewer, $id] = $this->submittedApp(['onboarding:review:view', 'onboarding:review:reject']);
        $base = "/api/v1/onboarding/review/{$id}:reject";
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($reviewer);

        $actor()->postJson($base, [], $headers)->assertStatus(422);
        $actor()->postJson($base, ['reason_code' => 'OTHER', 'second_reviewer' => (string) $reviewer->id], $headers)
            ->assertStatus(422);
        $actor()->postJson($base, ['reason_code' => 'OTHER', 'secondReviewer' => 'x'], $headers)
            ->assertStatus(422);
        $actor()->postJson($base, ['reason_code' => 'OTHER', 'second_reviewer' => 'r2'], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'REJECTED');
    }

    public function test_request_info_returns_to_draft(): void
    {
        [$tenant, $reviewer, $id] = $this->submittedApp(['onboarding:review:view']);

        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/review/{$id}:request-info", [], $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonPath('data.status', 'DRAFT');
    }
}
