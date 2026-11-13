<?php

namespace Tests\Feature\Verification;

use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\StateMachine\ApplicationStateMachine;
use App\Domain\Verification\StateMachine\IllegalStateTransitionException;
use App\Models\User;
use App\Modules\Onboarding\Models\ApplicationDocument;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Audit\AuditLog;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpQuorumRequiredException;
use App\Platform\Identity\Otp\OtpVerifyStatus;
use App\Platform\Tenancy\TenantScope;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Tests T-TR02-01..T-TR02-05 (Closes T-06, T-07, T-08, T-12, T-13).
 */
final class PhaseTwoClaimsAndDualControlTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
    }

    private function validJpegBytes(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00" . str_repeat("\x12", 128);
    }

    private function createSubmittedAppWithDoc(\App\Platform\Tenancy\Models\Tenant $tenant, User $applicant): array
    {
        Storage::fake('local');
        $headers = $this->tenantHeaders($tenant);

        $started = $this->actingAs($applicant)->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers);
        $started->assertCreated();
        $appId = (string) $started->json('data.id');

        $upload = $this->actingAs($applicant)->post(
            "/api/v1/onboarding/applications/{$appId}/documents",
            ['kind' => 'id-proof', 'file' => UploadedFile::fake()->createWithContent('id.jpg', $this->validJpegBytes())],
            $headers,
        );
        $upload->assertCreated();
        $docId = (string) $upload->json('data.id');

        PartnerApplication::withoutGlobalScope(TenantScope::class)->where('id', $appId)->update([
            'status' => PartnerApplication::STATUS_UNDER_REVIEW,
            'submitted_at' => now()->utc(),
            'evidence' => json_encode([
                'identity' => ['name' => 'Sharma', 'phone' => '+919876543210'],
                'credentials' => ['certifications' => ['vedic']],
                'services' => ['griha-pravesh'],
                'areas' => ['delhi'],
                'availability' => ['set' => true],
                'documents' => [$docId],
                'agreement' => ['accepted' => true],
            ]),
        ]);

        return [$appId, $docId];
    }

    public function test_claim_manager_grants_supersedes_and_derives_honest_trust_labels(): void
    {
        $tenant = $this->makeTenant();
        [$user, $membership] = $this->actingAsMembership($tenant);
        $this->resolveContext($tenant, $membership);

        $claims = app(ClaimManager::class);

        $first = $claims->grantClaim(
            attributes: [
                'tenant_id' => (string) $tenant->id,
                'subject_type' => 'pandit',
                'subject_id' => (string) $user->id,
                'claim_kind' => ClaimKind::QUALIFICATION->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::MEDIUM->value,
                'decided_by' => 'rev_1',
            ],
            scopes: [['scope_type' => 'tradition', 'scope_value' => 'smarta']],
            evidenceItems: [['evidence_kind' => 'certificate', 'source_mode' => 'manual', 'sha256' => str_repeat('a', 64)]],
        );

        $summaryManual = $claims->summarizeTrustProfile((string) $tenant->id, 'pandit', (string) $user->id);
        $this->assertSame('manually_reviewed', $summaryManual['badge_tier']);
        $this->assertSame('Manually Reviewed', $summaryManual['label']);

        $second = $claims->grantClaim([
            'tenant_id' => (string) $tenant->id,
            'subject_type' => 'pandit',
            'subject_id' => (string) $user->id,
            'claim_kind' => ClaimKind::QUALIFICATION->value,
            'mode' => EvidenceMode::OFFICIAL->value,
            'confidence' => ConfidenceLevel::HIGH->value,
            'decided_by' => 'rev_2',
        ]);

        $this->assertSame('superseded', $first->fresh()->status);
        $this->assertSame('granted', $second->fresh()->status);

        $summaryOfficial = $claims->summarizeTrustProfile((string) $tenant->id, 'pandit', (string) $user->id);
        $this->assertSame('verified_authoritative', $summaryOfficial['badge_tier']);
        $this->assertSame('Verified (Official Registry)', $summaryOfficial['label']);
    }

    public function test_application_state_machine_rejects_illegal_transitions(): void
    {
        $sm = new ApplicationStateMachine();
        $this->assertCount(15, ApplicationStateMachine::STATES);
        $this->assertTrue($sm->canTransition('DRAFT', 'SUBMITTED'));
        $this->assertTrue($sm->canTransition('UNDER_REVIEW', 'COUNTERSIGN_PENDING'));
        $this->assertTrue($sm->canTransition('COUNTERSIGN_PENDING', 'APPROVED'));

        $this->expectException(IllegalStateTransitionException::class);
        $sm->assertTransition('DRAFT', 'APPROVED');
    }

    public function test_dual_control_enforces_segregation_evidence_hash_drift_and_countersign(): void
    {
        $tenant = $this->makeTenant();
        [$applicant, $appMembership] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        [$appId, $docId] = $this->createSubmittedAppWithDoc($tenant, $applicant);
        $headers = $this->tenantHeaders($tenant);

        // Applicant self-approval MUST be rejected & audited.
        $this->stepUp($applicant);
        $this->actingAs($applicant)
            ->postJson("/api/v1/onboarding/review/{$appId}:initiate", ['decision' => 'approve'], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'segregation_violation');

        $this->assertSame(1, AuditLog::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', (string) $tenant->id)
            ->where('action', 'onboarding.control.segregation_violation')
            ->count());

        // Reviewer 1 initiates approval -> COUNTERSIGN_PENDING bound to evidence_set_hash.
        [$rev1, $rev1Mem] = $this->actingAsMembership($tenant, ['onboarding:review:view', 'onboarding:review:approve']);
        [$rev2, $rev2Mem] = $this->actingAsMembership($tenant, ['onboarding:review:view', 'onboarding:review:approve']);

        $this->stepUp($rev1);
        $init = $this->actingAs($rev1)
            ->postJson("/api/v1/onboarding/review/{$appId}:initiate", ['decision' => 'approve', 'note' => 'Looks solid'], $headers);
        $init->assertOk()->assertJsonPath('data.status', 'COUNTERSIGN_PENDING');
        $decisionId = (string) $init->json('data.decision_id');

        // Same reviewer cannot countersign their own initiated decision.
        $this->actingAs($rev1)
            ->postJson("/api/v1/onboarding/review/{$appId}:countersign", ['decision_id' => $decisionId], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'segregation_violation');

        // Simulate evidence tampering after initiation -> Reviewer 2 MUST hit 409 evidence_drifted!
        $this->stepUp($rev2);
        ApplicationDocument::withoutGlobalScope(TenantScope::class)->where('id', $docId)->update(['sha256' => str_repeat('b', 64)]);

        $this->actingAs($rev2)
            ->postJson("/api/v1/onboarding/review/{$appId}:countersign", ['decision_id' => $decisionId], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'evidence_drifted');

        // Initiate a fresh decision after hash change and countersign cleanly as Reviewer 2.
        $this->stepUp($rev1);
        $init2 = $this->actingAs($rev1)
            ->postJson("/api/v1/onboarding/review/{$appId}:initiate", ['decision' => 'approve'], $headers);
        $init2->assertOk();
        $decisionId2 = (string) $init2->json('data.decision_id');

        $this->stepUp($rev2);
        $final = $this->actingAs($rev2)
            ->postJson("/api/v1/onboarding/review/{$appId}:countersign", ['decision_id' => $decisionId2, 'note' => 'Concur'], $headers);
        $final->assertOk()->assertJsonPath('data.status', 'APPROVED');

        $this->assertDatabaseHas('onboarding_decisions', [
            'id' => $decisionId2,
            'status' => 'finalized',
            'primary_reviewer_id' => (string) $rev1->id,
            'second_reviewer_id' => (string) $rev2->id,
        ], 'tenant_shared');

        $this->assertSame(1, VerificationClaim::query()
            ->withoutGlobalScopes()
            ->where('application_id', $appId)
            ->where('status', 'granted')
            ->count());
    }

    public function test_document_review_separates_storage_integrity_from_compliance_status(): void
    {
        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant);
        [$appId, $docId] = $this->createSubmittedAppWithDoc($tenant, $applicant);
        [$rev, $revMem] = $this->actingAsMembership($tenant, ['onboarding:review:approve', 'onboarding:review:reject']);
        $headers = $this->tenantHeaders($tenant);

        // Corrupt underlying bytes on disk -> compliance verify MUST refuse with 409!
        $doc = ApplicationDocument::withoutGlobalScope(TenantScope::class)->findOrFail($docId);
        Storage::disk('local')->put((string) $doc->object_key, 'tampered');

        $this->stepUp($rev);
        $this->actingAs($rev)
            ->postJson("/api/v1/onboarding/review/{$appId}/documents/{$docId}:verify", [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'evidence_integrity_failed');

        // Upload clean second doc -> verify succeeds and records reviewer metadata.
        $upload2 = $this->actingAs($applicant)->post(
            "/api/v1/onboarding/applications/{$appId}/documents",
            ['kind' => 'certification', 'file' => UploadedFile::fake()->createWithContent('cert.jpg', $this->validJpegBytes())],
            $headers,
        );
        $upload2->assertCreated();
        $docId2 = (string) $upload2->json('data.id');

        $this->stepUp($rev);
        $this->actingAs($rev)
            ->postJson("/api/v1/onboarding/review/{$appId}/documents/{$docId2}:verify", ['expires_at' => '2028-01-01T00:00:00Z'], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'VERIFIED')
            ->assertJsonPath('data.integrity_state', 'verified')
            ->assertJsonPath('data.reviewed_by', (string) $rev->id);

        // Reject first document -> status REJECTED with closed reason code while integrity_state remains distinct.
        $this->actingAs($rev)
            ->postJson("/api/v1/onboarding/review/{$appId}/documents/{$docId}:reject", ['reason_code' => 'HASH_MISMATCH'], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'REJECTED')
            ->assertJsonPath('data.rejection_reason_code', 'HASH_MISMATCH');
    }

    public function test_t2_otp_purpose_rejects_single_channel_bypass_and_enforces_quorum(): void
    {
        $tenant = $this->makeTenant();
        $now = CarbonImmutable::parse('2026-11-11T10:00:00Z');
        $manager = app(OtpChallengeManager::class);

        $c1 = $manager->issue((string) $tenant->id, 'change_phone', '+919876543210', $now);
        $c2 = $manager->issue((string) $tenant->id, 'change_phone', '+919876543211', $now);

        $threwQuorum = false;
        try {
            $manager->verify((string) $tenant->id, ['change_phone'], $c1->id, $this->msg91TokenFor('+919876543210'), $now);
        } catch (OtpQuorumRequiredException) {
            $threwQuorum = true;
        }
        $this->assertTrue($threwQuorum, 'Single-channel verify() must throw OtpQuorumRequiredException for T2/T3.');

        $result = $manager->verifyQuorum(
            (string) $tenant->id,
            ['change_phone'],
            $c1->id,
            $this->msg91TokenFor('+919876543210'),
            $c2->id,
            $this->msg91TokenFor('+919876543211'),
            $now,
        );

        $this->assertTrue($result['ok']);
        $this->assertSame(OtpVerifyStatus::Match , $result['primary']->status);
        $this->assertSame(OtpVerifyStatus::Match , $result['secondary']->status);
    }
}
