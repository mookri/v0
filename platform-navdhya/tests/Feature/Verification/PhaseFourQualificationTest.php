<?php

namespace Tests\Feature\Verification;

use App\Domain\Privacy\Consent\ConsentManager;
use App\Domain\Privacy\Enums\ConsentPurpose;
use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Issuers\CompetencyAssessor;
use App\Domain\Verification\Issuers\CredentialCategory;
use App\Domain\Verification\Issuers\DisabledAuthoritativeCredentialAdapter;
use App\Domain\Verification\Issuers\OutreachStage;
use App\Domain\Verification\Issuers\QualificationEquivalenceService;
use App\Domain\Verification\Models\VerificationClaim;
use App\Models\User;
use App\Modules\Onboarding\StepValidators;
use Database\Seeders\VerificationIssuerSeeder;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TenantIntegrationTestCase;

/**
 * Phase 4 (E-TR-03): Evidence-graded issuer directory, closed credential taxonomy (T-19),
 * consent-gated outreach, mode ceiling, non-response handling, competency scopes, and revocation.
 */
final class PhaseFourQualificationTest extends TenantIntegrationTestCase
{
    public function test_step_validator_rejects_free_text_and_accepts_closed_credential_taxonomy(): void
    {
        $bad = StepValidators::validate('credentials', ['certifications' => ['My Unverified Baba Certificate']]);
        $this->assertSame(['certifications' => ['invalid_credential_category']], $bad);

        $good = StepValidators::validate('credentials', [
            'certifications' => [
                ['credential_type' => 'shastri', 'issuer_code' => 'sampurnanand_sanskrit_univ'],
            ]
        ]);
        $this->assertSame([], $good);

        $jyotish = CredentialCategory::fromInput('jyotisha');
        $this->assertStringContainsString('never an assurance of predictive accuracy', (string) $jyotish->scopeDisclosure());
    }

    public function test_seeder_populates_graded_issuers_and_endpoint_lists_them(): void
    {
        $this->seed(VerificationIssuerSeeder::class);

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $res = $this->actingAs($user)->getJson('/api/v1/onboarding/issuers', $this->tenantHeaders($tenant));
        $res->assertOk();
        $codes = collect($res->json('data.issuers'))->pluck('code')->all();
        $this->assertContains('sampurnanand_sanskrit_univ', $codes);
        $this->assertContains('unconfirmed_parampara_guru', $codes);
    }

    public function test_outreach_requires_explicit_qualification_outreach_consent(): void
    {
        $this->seed(VerificationIssuerSeeder::class);

        $tenant = $this->makeTenant();
        [$reviewer, $reviewerMembership] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        $applicant = User::factory()->create();
        $applicantMembership = $this->makeMembership($tenant, $applicant);
        $headers = $this->tenantHeaders($tenant);

        $registered = $this->actingAs($applicant)->postJson('/api/v1/onboarding/credentials', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_01',
            'issuer_code' => 'sampurnanand_sanskrit_univ',
            'credential_type' => 'acharya',
            'certificate_ref' => 'SSVV-ACH-2019-9981',
        ], $headers)->assertCreated();

        $credId = $registered->json('data.credential_id');

        // Without consent -> fail closed 403 consent_required.
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/credentials/verify', [
            'credential_id' => $credId,
        ], $headers)->assertStatus(403)->assertJsonPath('error.code', 'consent_required');

        // Grant unrelated consent -> still blocked (non-cascading).
        $this->resolveContext($tenant, $applicantMembership);
        app(ConsentManager::class)->grant($tenant->id, (string) $applicant->id, ConsentPurpose::IDENTITY_KYC);
        $this->clearContext();
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/credentials/verify', [
            'credential_id' => $credId,
        ], $headers)->assertStatus(403);

        // Grant QUALIFICATION_OUTREACH consent -> succeeds.
        $this->resolveContext($tenant, $applicantMembership);
        app(ConsentManager::class)->grant($tenant->id, (string) $applicant->id, ConsentPurpose::QUALIFICATION_OUTREACH);
        $this->clearContext();
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/credentials/verify', [
            'credential_id' => $credId,
        ], $headers)->assertOk()->assertJsonPath('data.outreach_stage', 'REQUEST_SENT');
    }

    public function test_mode_ceiling_enforced_for_non_authoritative_and_confirmed_issuers(): void
    {
        $this->seed(VerificationIssuerSeeder::class);

        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        $this->stepUp($reviewer);
        $headers = $this->tenantHeaders($tenant);

        $unconfirmed = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/credentials', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_02',
            'issuer_code' => 'kashi_gurukul_pathashala',
            'credential_type' => 'veda_pathashala',
            'certificate_ref' => 'KVP-1999-44',
        ], $headers)->assertCreated()->json('data.credential_id');

        // Attempting official mode on contact_found issuer fails closed 422 mode_ceiling_violation.
        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/credentials/{$unconfirmed}/outcome", [
            'stage' => OutreachStage::RESPONSE_RECEIVED->value,
            'mode' => EvidenceMode::OFFICIAL->value,
            'responder_name' => 'Acharya Shukla',
        ], $headers)->assertStatus(422)->assertJsonPath('error.code', 'mode_ceiling_violation');

        // Manual mode succeeds cleanly.
        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/credentials/{$unconfirmed}/outcome", [
            'stage' => OutreachStage::RESPONSE_RECEIVED->value,
            'mode' => EvidenceMode::MANUAL->value,
            'responder_name' => 'Acharya Shukla',
        ], $headers)->assertOk()->assertJsonPath('data.claim_mode', 'manual');

        // Authoritative issuer STILL blocked from official mode if responder_name missing.
        $authCred = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/credentials', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_03',
            'issuer_code' => 'sampurnanand_sanskrit_univ',
            'credential_type' => 'shastri',
            'certificate_ref' => 'SSVV-2015-77',
        ], $headers)->assertCreated()->json('data.credential_id');

        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/credentials/{$authCred}/outcome", [
            'stage' => OutreachStage::RESPONSE_RECEIVED->value,
            'mode' => EvidenceMode::OFFICIAL->value,
        ], $headers)->assertStatus(422)->assertJsonPath('error.code', 'mode_ceiling_violation');

        // Authoritative issuer WITH confirmed responder unlocks official mode.
        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/credentials/{$authCred}/outcome", [
            'stage' => OutreachStage::RESPONSE_RECEIVED->value,
            'mode' => EvidenceMode::OFFICIAL->value,
            'responder_name' => 'Dr. V.N. Pandey',
            'responder_role' => 'Controller of Examinations',
            'official_response_ref' => 'REG-OUT-2026-102',
        ], $headers)->assertOk()->assertJsonPath('data.claim_mode', 'official');
    }

    public function test_non_response_from_offline_pathashala_routes_to_manual_appealable_claim_and_denial_escalates(): void
    {
        $this->seed(VerificationIssuerSeeder::class);

        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        $this->stepUp($reviewer);
        $headers = $this->tenantHeaders($tenant);

        $credNoResp = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/credentials', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_04',
            'issuer_code' => 'unconfirmed_parampara_guru',
            'credential_type' => 'parampara_gurukul_sanad',
            'certificate_ref' => 'ORAL-2010-12',
        ], $headers)->assertCreated()->json('data.credential_id');

        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/credentials/{$credNoResp}/outcome", [
            'stage' => OutreachStage::NO_RESPONSE->value,
        ], $headers)->assertOk()
            ->assertJsonPath('data.claim_mode', 'manual')
            ->assertJsonPath('data.claim_confidence', 'low');

        $credDenied = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/credentials', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_05',
            'issuer_code' => 'sampurnanand_sanskrit_univ',
            'credential_type' => 'shastri',
            'certificate_ref' => 'FORGED-001',
        ], $headers)->assertCreated()->json('data.credential_id');

        $deniedRes = $this->actingAs($reviewer)->postJson("/api/v1/onboarding/credentials/{$credDenied}/outcome", [
            'stage' => OutreachStage::DENIED->value,
            'notes' => 'Roll number not in registrar books.',
        ], $headers)->assertOk();

        $claim = VerificationClaim::withoutGlobalScopes()->findOrFail($deniedRes->json('data.claim_id'));
        $this->assertSame(ClaimKind::ENHANCED_REVIEW->value, $claim->claim_kind);
    }

    public function test_practical_competency_gates_booking_scope_strictly(): void
    {
        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        $this->stepUp($reviewer);

        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/competency/assess', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_scope_01',
            'assessor_identity_id' => 'senior_acharya_9',
            'ritual_category' => 'karmakanda',
            'scope_tags' => ['griha_pravesh', 'satyanarayan_puja'],
            'rubric_scores' => ['mantra_svara' => 88, 'vidhi_accuracy' => 92],
            'sampradaya' => 'Smarta',
        ], $this->tenantHeaders($tenant))->assertOk()->assertJsonPath('data.passed', true);

        $assessor = app(CompetencyAssessor::class);
        $this->assertTrue($assessor->canPerformRitual($tenant->id, 'pandit', 'pandit_scope_01', 'griha_pravesh'));
        $this->assertFalse($assessor->canPerformRitual($tenant->id, 'pandit', 'pandit_scope_01', 'mahamrityunjaya'));
    }

    public function test_duplicate_certificate_reuse_and_selective_revocation_spare_identity_claim(): void
    {
        $this->seed(VerificationIssuerSeeder::class);

        $tenant = $this->makeTenant();
        [$reviewer, $reviewerMembership] = $this->actingAsMembership($tenant, ['onboarding:review:approve', 'onboarding:claim:revoke']);
        $this->stepUp($reviewer);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->makeMembership($tenant, $userA);
        $this->makeMembership($tenant, $userB);
        $headers = $this->tenantHeaders($tenant);

        // Grant unrelated identity claim for pandit_dup.
        $this->resolveContext($tenant, $reviewerMembership);
        $idClaim = app(ClaimManager::class)->grantClaim([
            'tenant_id' => $tenant->id,
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_dup',
            'claim_kind' => ClaimKind::IDENTITY->value,
            'mode' => EvidenceMode::MANUAL->value,
            'decided_by' => (string) $reviewer->id,
        ]);
        $this->clearContext();

        // First registration by userA.
        $credA = $this->actingAs($userA)->postJson('/api/v1/onboarding/credentials', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_dup',
            'issuer_code' => 'sampurnanand_sanskrit_univ',
            'credential_type' => 'shastri',
            'certificate_ref' => 'SHARED-CERT-1234',
        ], $headers)->assertCreated();
        $this->assertFalse($credA->json('data.duplicate_detected'));

        // Same issuer + cert ref submitted by userB -> triggers duplicate signal.
        $credB = $this->actingAs($userB)->postJson('/api/v1/onboarding/credentials', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_dup_other',
            'issuer_code' => 'sampurnanand_sanskrit_univ',
            'credential_type' => 'shastri',
            'certificate_ref' => 'SHARED-CERT-1234',
        ], $headers)->assertCreated();
        $this->assertTrue($credB->json('data.duplicate_detected'));

        // Verify credA then revoke it -> identity claim must stay GRANTED.
        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/credentials/{$credA->json('data.credential_id')}/outcome", [
            'stage' => OutreachStage::RESPONSE_RECEIVED->value,
            'mode' => EvidenceMode::OFFICIAL->value,
            'responder_name' => 'Registrar SSVV',
        ], $headers)->assertOk();

        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/credentials/{$credA->json('data.credential_id')}/revoke", [
            'reason' => 'duplicate_submission_investigation',
        ], $headers)->assertOk();

        $this->assertSame(ClaimStatus::GRANTED->value, VerificationClaim::withoutGlobalScopes()->findOrFail($idClaim->id)->status);
    }

    public function test_nad_digilocker_adapter_is_gated_and_equivalence_score_isolated(): void
    {
        $tenant = $this->makeTenant();

        try {
            app(DisabledAuthoritativeCredentialAdapter::class)->verifyFromRegistry($tenant->id, 'user_1', 'sampurnanand_sanskrit_univ', 'REF1');
            $this->fail('Expected ProviderNotApprovedException to fail closed.');
        } catch (RuntimeException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        // Also assert QualificationEquivalenceService is never referenced inside ClaimManager.
        $claimManagerSource = file_get_contents(app_path('Domain/Verification/Claims/ClaimManager.php'));
        $this->assertIsString($claimManagerSource);
        $this->assertFalse(Str::contains($claimManagerSource, ['prioritizationScore', 'QualificationEquivalenceService']));

        $equiv = app(QualificationEquivalenceService::class)->equivalenceAdvisory(CredentialCategory::SHASTRI);
        $this->assertSame('undergraduate_shastric', $equiv['equivalent_tier']);
    }
}
