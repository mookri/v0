<?php

namespace Tests\Feature\Verification;

use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Lifecycle\DecisionExplanationService;
use App\Domain\Verification\Lifecycle\ReviewQueuePrioritizer;
use App\Models\User;
use App\Modules\Onboarding\Models\PartnerApplication;
use Tests\TenantIntegrationTestCase;

/**
 * Phase 8 (E-TR-08): Logged workload/skill queue routing, partner status truth,
 * plain-language explanations, and data-derived customer trust badge DTOs (Closes T-02).
 */
final class PhaseEightTransparencyAndQueueTest extends TenantIntegrationTestCase
{
    public function test_prioritized_queue_computes_logged_component_scores_and_skill_routing(): void
    {
        $prioritizer = new ReviewQueuePrioritizer();
        $temple = $prioritizer->score('temple', 0.90, 72, 0.80, 0.85);
        $this->assertSame('compliance', $temple['recommended_skill']);
        $this->assertGreaterThan(0.5, $temple['priority_score']);
        $this->assertStringContainsString('Routed to [compliance]', $temple['routing_rationale']);

        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:view']);
        $headers = $this->tenantHeaders($tenant);

        PartnerApplication::create([
            'tenant_id' => $tenant->id,
            'identity_id' => 'user_q_1',
            'track' => 'pandit',
            'track_version' => 'pandit-v1',
            'status' => PartnerApplication::STATUS_SUBMITTED,
            'market' => 'IN',
        ]);
        PartnerApplication::create([
            'tenant_id' => $tenant->id,
            'identity_id' => 'user_q_2',
            'track' => 'temple',
            'track_version' => 'temple-v1',
            'status' => PartnerApplication::STATUS_ESCALATED,
            'market' => 'IN',
        ]);

        $res = $this->actingAs($reviewer)->getJson('/api/v1/onboarding/admin/onboarding/queue', $headers);
        $res->assertOk();
        $queue = $res->json('data.queue');
        $this->assertCount(2, $queue);
        $this->assertSame('ESCALATED', $queue[0]['status']);
        $this->assertSame('compliance', $queue[0]['recommended_skill']);
        $this->assertArrayHasKey('component_scores', $queue[0]);
    }

    public function test_partner_status_and_trust_badge_reflect_honest_claim_modes_and_explanations(): void
    {
        $tenant = $this->makeTenant();
        [$partner, $membership] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        PartnerApplication::create([
            'tenant_id' => $tenant->id,
            'identity_id' => (string) $partner->id,
            'track' => 'pandit',
            'track_version' => 'pandit-v1',
            'status' => PartnerApplication::STATUS_REJECTED,
            'market' => 'IN',
            'rejection_code' => 'DOC_ILLEGIBLE',
        ]);

        $this->resolveContext($tenant, $membership);
        app(ClaimManager::class)->grantClaim([
            'tenant_id' => $tenant->id,
            'subject_type' => 'pandit',
            'subject_id' => (string) $partner->id,
            'claim_kind' => ClaimKind::QUALIFICATION->value,
            'mode' => EvidenceMode::MANUAL->value,
            'decided_by' => 'reviewer_1',
        ]);
        $this->clearContext();

        $statusRes = $this->actingAs($partner)->getJson('/api/v1/onboarding/me/onboarding-status', $headers);
        $statusRes->assertOk()
            ->assertJsonPath('data.application_status', PartnerApplication::STATUS_REJECTED)
            ->assertJsonPath('data.trust_summary.label', 'Manually Reviewed')
            ->assertJsonPath('data.explanation.appealable', true)
            ->assertJsonPath('data.explanation.headline', 'Document clarification required');

        $this->assertStringContainsString("onboarding-status:{$tenant->id}:{$partner->id}", (string) $statusRes->json('data.revalidation_tag'));

        // Customer-facing trust badge DTO never mislabels manual review as "Official Verified".
        $badgeRes = $this->actingAs($partner)->getJson("/api/v1/onboarding/partners/pandit/{$partner->id}/trust-badge", $headers);
        $badgeRes->assertOk()
            ->assertJsonPath('data.label', 'Manually Reviewed')
            ->assertJsonPath('data.highest_mode', 'manual');

        $explainer = new DecisionExplanationService();
        $offline = $explainer->explain('ISSUER_NO_RESPONSE_MANUAL_FALLBACK', PartnerApplication::STATUS_UNDER_REVIEW);
        $this->assertStringContainsString('not treated as negative', $offline['explanation']);
    }
}

