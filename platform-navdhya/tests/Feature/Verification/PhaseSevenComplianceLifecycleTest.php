<?php

namespace Tests\Feature\Verification;

use App\Domain\Privacy\Consent\ConsentManager;
use App\Domain\Privacy\Enums\ConsentPurpose;
use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Lifecycle\MaterialChangeReverificationListener;
use App\Domain\Verification\Lifecycle\ReviewerQualityMetricsService;
use App\Domain\Verification\Models\OnboardingDecision;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Risk\BackgroundCheckService;
use App\Domain\Verification\Risk\PoliceAntecedentStatus;
use App\Domain\Verification\Risk\RiskSignalRegistry;
use App\Models\User;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Audit\AuditWriter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Tests\TenantIntegrationTestCase;

/**
 * Phase 7 (E-TR-07): Nuanced background checks (§9.4), G-LEGAL-0 AML gate,
 * partner appeals (T-16), claim expiry (T-18), scoped revocation, and rubber-stamp metrics.
 */
final class PhaseSevenComplianceLifecycleTest extends TenantIntegrationTestCase
{
    public function test_background_check_schema_lacks_criminal_boolean_requires_consent_and_human_review(): void
    {
        $this->assertFalse(
            Schema::connection('tenant_shared')->hasColumn('verification_background_checks', 'criminal_record'),
        );

        $tenant = $this->makeTenant();
        [$reviewer, $reviewerMembership] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        $this->stepUp($reviewer);
        $applicant = User::factory()->create();
        $applicantMembership = $this->makeMembership($tenant, $applicant);
        $headers = $this->tenantHeaders($tenant);

        // 1. Police antecedents without ANTECEDENT_CHECK consent fails closed 403.
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/admin/onboarding/background-checks', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_bg_1',
            'identity_id' => (string) $applicant->id,
            'check_type' => 'police_antecedents',
            'lawful_basis' => 'explicit_consent',
            'legal_status' => PoliceAntecedentStatus::CLEAR->value,
        ], $headers)->assertStatus(403)->assertJsonPath('error.code', 'consent_required');

        // 2. Grant ANTECEDENT_CHECK consent -> 'complaint_filed' sets action_required = true and blocks auto-reject.
        $this->resolveContext($tenant, $applicantMembership);
        app(ConsentManager::class)->grant($tenant->id, (string) $applicant->id, ConsentPurpose::ANTECEDENT_CHECK);
        $this->clearContext();

        $checkRes = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/admin/onboarding/background-checks', [
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_bg_1',
            'identity_id' => (string) $applicant->id,
            'check_type' => 'police_antecedents',
            'lawful_basis' => 'explicit_consent',
            'legal_status' => PoliceAntecedentStatus::COMPLAINT_FILED->value,
            'findings_summary' => 'Unverified boundary dispute complaint filed.',
        ], $headers)->assertCreated()->assertJsonPath('data.action_required', true);

        $checkId = $checkRes->json('data.check_id');

        // Without human relevance assessment -> blocked from rejection.
        try {
            app(BackgroundCheckService::class)->assertCanRejectBasedOnCheck($tenant->id, $checkId);
            $this->fail('Expected rejection guard before clarification.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('human relevance assessment', $e->getMessage());
        }

        // Record applicant clarification & human relevance review -> clears guard.
        $this->actingAs($reviewer)->postJson("/api/v1/onboarding/admin/onboarding/background-checks/{$checkId}/clarify", [
            'clarification' => 'Civil boundary matter resolved amicably in Lok Adalat.',
        ], $headers)->assertOk()->assertJsonPath('data.human_relevance_assessed', true);

        app(BackgroundCheckService::class)->assertCanRejectBasedOnCheck($tenant->id, $checkId);
    }

    public function test_risk_signal_registry_bans_discriminatory_attributes(): void
    {
        $registry = new RiskSignalRegistry();
        $this->assertTrue($registry->isRegistered('DUPLICATE_CERTIFICATE_CLAIM'));

        $this->expectException(InvalidArgumentException::class);
        $registry->register('CASTE_RISK_FLAG', 'Using caste surname heuristic for risk.');
    }

    public function test_regulatory_applicability_gate_g_legal_0_fails_closed_until_counsel_clearance(): void
    {
        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:view']);
        $headers = $this->tenantHeaders($tenant);

        // Default unclassified gate fails closed 403.
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/admin/onboarding/sanctions/screen', [
            'full_name' => 'Designated Test Entity One',
            'date_of_birth' => '1970-01-01',
        ], $headers)->assertStatus(403)->assertJsonPath('error.code', 'regulatory_gate_locked');

        // With signed counsel classification -> unlocks and produces POSSIBLE_MATCH (human queue, not auto-reject).
        DB::connection('central')->table('verification_regulatory_gates')->insert([
            'id' => (string) Str::ulid(),
            'gate_key' => 'pmla_fiu_reporting_entity',
            'classification_status' => 'classified_reporting_entity',
            'counsel_opinion_ref' => 'COUNSEL-OP-2026-88',
            'signed_at' => now()->utc(),
            'aml_features_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/admin/onboarding/sanctions/screen', [
            'full_name' => 'Designated Test Entity One',
            'date_of_birth' => '1970-01-01',
        ], $headers)->assertOk()
            ->assertJsonPath('data.outcome', 'POSSIBLE_MATCH')
            ->assertJsonPath('data.human_review_required', true);
    }

    public function test_appeals_lifecycle_enforces_independent_decider_and_overturns_to_under_review(): void
    {
        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant, ['onboarding:appeal:submit']);
        [$originalReviewer] = $this->actingAsMembership($tenant, ['onboarding:appeal:decide', 'onboarding:review:view']);
        $this->stepUp($originalReviewer);

        [$independentReviewer] = $this->actingAsMembership($tenant, ['onboarding:appeal:decide', 'onboarding:review:view']);
        $this->stepUp($independentReviewer);

        $headers = $this->tenantHeaders($tenant);

        $app = PartnerApplication::create([
            'tenant_id' => $tenant->id,
            'identity_id' => (string) $applicant->id,
            'track' => 'pandit',
            'track_version' => 'pandit-v1',
            'status' => PartnerApplication::STATUS_REJECTED,
            'market' => 'IN',
            'rejection_code' => 'DOC_ILLEGIBLE',
            'reviewer_id' => $originalReviewer->id,
        ]);

        OnboardingDecision::create([
            'tenant_id' => $tenant->id,
            'application_id' => (string) $app->getKey(),
            'decision' => 'reject',
            'status' => 'countersigned',
            'reason_code' => 'DOC_ILLEGIBLE',
            'evidence_set_hash' => hash('sha256', 'appeal-fixture'),
            'primary_reviewer_id' => (string) $originalReviewer->id,
            'second_reviewer_id' => 'countersigner_9',
        ]);

        $appeal = $this->actingAs($applicant)->postJson('/api/v1/onboarding/appeals', [
            'application_id' => (string) $app->getKey(),
            'grounds_code' => 'NEW_SCANNED_DOCUMENTS',
            'applicant_statement' => 'Uploaded high-resolution Sanad scan and notarized gazette copy.',
            'new_evidence_ids' => ['doc_hd_01'],
        ], $headers)->assertCreated();

        $appealId = $appeal->json('data.appeal_id');
        $this->assertSame(PartnerApplication::STATUS_APPEALED, PartnerApplication::withoutGlobalScopes()->findOrFail($app->getKey())->status);

        // Original decider trying to rule on appeal MUST be blocked 403 appeal_decider_conflict.
        $this->actingAs($originalReviewer)->postJson("/api/v1/onboarding/admin/onboarding/appeals/{$appealId}/decide", [
            'outcome' => 'upheld',
            'note' => 'Trying to uphold my own rejection.',
        ], $headers)->assertStatus(403)->assertJsonPath('error.code', 'appeal_decider_conflict');

        // Independent reviewer overturns -> transitions application cleanly back to UNDER_REVIEW.
        $this->actingAs($independentReviewer)->postJson("/api/v1/onboarding/admin/onboarding/appeals/{$appealId}/decide", [
            'outcome' => 'overturned',
            'note' => 'High-res Sanad copy confirmed authentic; reopening under review.',
        ], $headers)->assertOk()->assertJsonPath('data.outcome', 'overturned');

        $this->assertSame(PartnerApplication::STATUS_UNDER_REVIEW, PartnerApplication::withoutGlobalScopes()->findOrFail($app->getKey())->status);
    }

    public function test_verification_expire_scoped_revocation_and_material_change_reverification(): void
    {
        $tenant = $this->makeTenant();
        [$reviewer, $membership] = $this->actingAsMembership($tenant, ['onboarding:claim:revoke']);
        $this->stepUp($reviewer);
        $this->resolveContext($tenant, $membership);

        $claims = app(ClaimManager::class);

        // Overdue claim vs future claim.
        $overdue = $claims->grantClaim([
            'tenant_id' => $tenant->id,
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_exp_1',
            'claim_kind' => ClaimKind::TAX->value,
            'mode' => EvidenceMode::NATIVE->value,
            'decided_by' => (string) $reviewer->id,
            'expires_at' => now()->utc()->subDay(),
        ]);

        $freshFinancial = $claims->grantClaim([
            'tenant_id' => $tenant->id,
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_exp_1',
            'claim_kind' => ClaimKind::FINANCIAL->value,
            'mode' => EvidenceMode::FINANCIAL->value,
            'decided_by' => (string) $reviewer->id,
        ]);

        $freshQualification = $claims->grantClaim([
            'tenant_id' => $tenant->id,
            'subject_type' => 'pandit',
            'subject_id' => 'pandit_exp_1',
            'claim_kind' => ClaimKind::QUALIFICATION->value,
            'mode' => EvidenceMode::MANUAL->value,
            'decided_by' => (string) $reviewer->id,
        ]);

        $this->clearContext();
        Artisan::call('verification:expire', ['--tenant' => $tenant->id]);

        $this->assertSame(ClaimStatus::EXPIRED->value, VerificationClaim::withoutGlobalScopes()->findOrFail($overdue->id)->status);
        $this->assertSame(ClaimStatus::GRANTED->value, VerificationClaim::withoutGlobalScopes()->findOrFail($freshFinancial->id)->status);

        // Material change 'payout_instrument_changed' revokes ONLY FINANCIAL claim, sparing QUALIFICATION.
        $this->resolveContext($tenant, $membership);
        app(MaterialChangeReverificationListener::class)->handle(
            $tenant->id,
            'payout_instrument_changed',
            'pandit',
            'pandit_exp_1',
            (string) $reviewer->id,
        );
        $this->clearContext();

        $this->assertSame(ClaimStatus::REVOKED->value, VerificationClaim::withoutGlobalScopes()->findOrFail($freshFinancial->id)->status);
        $this->assertSame(ClaimStatus::GRANTED->value, VerificationClaim::withoutGlobalScopes()->findOrFail($freshQualification->id)->status);
    }

    public function test_reviewer_quality_metrics_flags_rubber_stamping_without_document_access(): void
    {
        $tenant = $this->makeTenant();
        [$reviewer, $membership] = $this->actingAsMembership($tenant, ['onboarding:review:view']);
        $this->resolveContext($tenant, $membership);

        // Emit approval WITHOUT prior onboarding.document.accessed event.
        app(AuditWriter::class)->record(
            'onboarding.application.approved',
            ['module' => 'onboarding', 'resource_type' => 'partner_application', 'resource_id' => 'app_rs_1'],
            null,
            ['decision' => 'approve'],
        );
        $this->clearContext();

        $res = app(ReviewerQualityMetricsService::class)->metricsForReviewer($tenant->id, (string) $reviewer->id);
        $this->assertSame(1, $res['total_decisions']);
        $this->assertSame(1, $res['decisions_without_prior_document_access']);
        $this->assertTrue($res['rubber_stamp_alert']);
    }
}
