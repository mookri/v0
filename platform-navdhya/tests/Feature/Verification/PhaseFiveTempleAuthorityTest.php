<?php

namespace Tests\Feature\Verification;

use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Models\VerificationClaim;
use Database\Seeders\VerificationJurisdictionSeeder;
use Tests\TenantIntegrationTestCase;

/**
 * Phase 5 (E-TR-04): Institutional existence vs representative authority graph,
 * jurisdiction checklists, conflicting-trustee locks, duplicate temples, and mandatory §7.2 takeover guard.
 */
final class PhaseFiveTempleAuthorityTest extends TenantIntegrationTestCase
{
    public function test_mandatory_section_7_2_trustee_takeover_adversarial_invariant(): void
    {
        $this->seed(VerificationJurisdictionSeeder::class);

        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:approve', 'onboarding:review:view']);
        $this->stepUp($reviewer);
        $headers = $this->tenantHeaders($tenant);

        // 1. Verify institutional existence (valid Maharashtra trust deed), mode strictly manual.
        $existence = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'existence',
            'entity_id' => 'temple_sidhivinayak_demo',
            'legal_form' => 'public_religious_trust',
            'jurisdiction_code' => 'MH_CHARITY_COMMISSIONER',
            'registration_number' => 'E-12345-MUM',
            'normalized_name' => 'Siddhivinayak Mandir Trust',
            'checklist' => [
                'trust_registration_number' => 'E-12345-MUM',
                'ptr_entry_screenshot_document_id' => 'doc_ptr_01',
                'verified_timestamp' => '2026-11-11T10:00:00Z',
                'reviewer_id' => (string) $reviewer->id,
            ],
        ], $headers);
        $existence->assertOk()
            ->assertJsonPath('data.claim_kind', ClaimKind::INSTITUTIONAL_EXISTENCE->value)
            ->assertJsonPath('data.claim_mode', 'manual');

        // 2. Attacker submits without institutional.authority grant -> MUST fail 403 on approve AND payout attach.
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/temple_sidhivinayak_demo/approve-check', [
            'applicant_identity_id' => 'attacker_user_99',
        ], $headers)->assertStatus(403)->assertJsonPath('error.code', 'institutional_authority_required');

        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/temple_sidhivinayak_demo/payout-instrument-check', [
            'applicant_identity_id' => 'attacker_user_99',
        ], $headers)->assertStatus(403)->assertJsonPath('error.code', 'institutional_authority_required');

        // 3. Grant scoped onboarding-only authority to real trustee -> approve unlocked, bank mandate STILL blocked!
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'authority',
            'entity_id' => 'temple_sidhivinayak_demo',
            'person_identity_id' => 'trustee_sharma_1',
            'role_title' => 'Managing Trustee',
            'authority_scope' => 'onboarding',
            'resolution_ref' => 'RES-2026-09',
            'resolution_date' => '2026-10-15',
            'registration_fingerprint_hmac' => $existence->json('data.registration_fingerprint_hmac'),
        ], $headers)->assertOk()->assertJsonPath('data.claim_kind', ClaimKind::INSTITUTIONAL_AUTHORITY->value);

        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/temple_sidhivinayak_demo/approve-check', [
            'applicant_identity_id' => 'trustee_sharma_1',
        ], $headers)->assertOk()->assertJsonPath('data.eligible', true);

        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/temple_sidhivinayak_demo/payout-instrument-check', [
            'applicant_identity_id' => 'trustee_sharma_1',
        ], $headers)->assertStatus(403)->assertJsonPath('error.code', 'bank_mandate_authority_required');

        // 4. Inspect bounded relationship graph returns authority edge + evidence_id.
        $this->actingAs($reviewer)->getJson('/api/v1/onboarding/institutions/temple_sidhivinayak_demo/graph', $headers)
            ->assertOk()
            ->assertJsonPath('data.edges.0.edge_type', 'has_authority_grant')
            ->assertJsonPath('data.edges.0.evidence_id', 'RES-2026-09');
    }

    public function test_unregistered_institution_is_not_rejected_and_routes_to_corroboration(): void
    {
        $this->seed(VerificationJurisdictionSeeder::class);

        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        $this->stepUp($reviewer);

        $res = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'existence',
            'entity_id' => 'temple_village_gramadevata',
            'legal_form' => 'unregistered_institution',
            'jurisdiction_code' => 'GENERAL_SOCIETY',
            'normalized_name' => 'Gramadevata Mandir Sitapur',
            'checklist' => [
                'society_certificate_or_community_letter' => 'panchayat_letter_doc_1',
                'electricity_or_land_title_proof' => 'eb_bill_doc_2',
                'multi_witness_corroboration' => 'witness_affidavits_3',
            ],
        ], $this->tenantHeaders($tenant));

        $res->assertOk()->assertJsonPath('data.claim_mode', 'manual');
        $claim = VerificationClaim::withoutGlobalScopes()->findOrFail($res->json('data.claim_id'));
        $this->assertSame('UNREGISTERED_INSTITUTION_MANUAL_CORROBORATED', $claim->reason_code);
        $this->assertSame(ClaimStatus::GRANTED->value, $claim->status);
    }

    public function test_conflicting_trustee_claim_and_duplicate_registration_escalate_to_enhanced_review(): void
    {
        $this->seed(VerificationJurisdictionSeeder::class);

        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        $this->stepUp($reviewer);
        $headers = $this->tenantHeaders($tenant);

        // Register first temple & full trustee authority.
        $first = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'existence',
            'entity_id' => 'temple_orig_01',
            'legal_form' => 'state_endowment_board',
            'jurisdiction_code' => 'TN_HRCE',
            'registration_number' => 'HRCE-TN-9001',
            'checklist' => [
                'hrce_temple_code' => 'HRCE-TN-9001',
                'executive_officer_appointment_order' => 'GO-101',
                'trustee_board_resolution' => 'BR-01',
            ],
        ], $headers)->assertOk();

        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'authority',
            'entity_id' => 'temple_orig_01',
            'person_identity_id' => 'trustee_alpha',
            'role_title' => 'Executive Trustee',
            'authority_scope' => 'full_representation',
            'resolution_ref' => 'BR-01',
            'resolution_date' => '2026-09-01',
            'registration_fingerprint_hmac' => $first->json('data.registration_fingerprint_hmac'),
        ], $headers)->assertOk();

        // Competing trustee attempts takeover on same entity -> blocked 409 conflicting_trustee_claim.
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'authority',
            'entity_id' => 'temple_orig_01',
            'person_identity_id' => 'trustee_beta_usurper',
            'role_title' => 'Executive Trustee',
            'authority_scope' => 'full_representation',
            'resolution_ref' => 'BR-DISPUTED',
            'resolution_date' => '2026-10-01',
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'conflicting_trustee_claim');

        // Duplicate registration number submitted under a new temple entity ID -> flags duplicate_detected.
        $dup = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'existence',
            'entity_id' => 'temple_clone_02',
            'legal_form' => 'state_endowment_board',
            'jurisdiction_code' => 'TN_HRCE',
            'registration_number' => 'HRCE-TN-9001',
            'checklist' => [
                'hrce_temple_code' => 'HRCE-TN-9001',
                'executive_officer_appointment_order' => 'GO-101',
                'trustee_board_resolution' => 'BR-01',
            ],
        ], $headers)->assertOk();

        $this->assertTrue($dup->json('data.duplicate_detected'));
    }

    public function test_institutional_bank_mandate_enforces_cosign_dual_review_and_48h_cooling_off(): void
    {
        $this->seed(VerificationJurisdictionSeeder::class);

        $tenant = $this->makeTenant();
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:approve']);
        $this->stepUp($reviewer);
        $headers = $this->tenantHeaders($tenant);

        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'existence',
            'entity_id' => 'temple_bank_01',
            'legal_form' => 'public_religious_trust',
            'jurisdiction_code' => 'KA_MUZRAI',
            'registration_number' => 'KA-MUZ-77',
            'checklist' => ['muzrai_category_grade' => 'Grade-A', 'management_committee_order' => 'MC-22'],
        ], $headers)->assertOk();

        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/verify', [
            'kind' => 'authority',
            'entity_id' => 'temple_bank_01',
            'person_identity_id' => 'treasurer_1',
            'role_title' => 'Treasurer',
            'authority_scope' => 'bank_mandate',
            'resolution_ref' => 'RES-BANK-22',
            'resolution_date' => '2026-10-01',
        ], $headers)->assertOk();

        // Same reviewer as countersigner -> blocked 422 dual_reviewer_required.
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/temple_bank_01/bank-mandate', [
            'applicant_identity_id' => 'treasurer_1',
            'co_sign_trustee_id' => 'president_2',
            'reviewer_id' => (string) $reviewer->id,
            'countersigner_id' => (string) $reviewer->id,
        ], $headers)->assertStatus(422)->assertJsonPath('error.code', 'dual_reviewer_required');

        // Self co-sign -> blocked 422 second_trustee_cosign_required.
        $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/temple_bank_01/bank-mandate', [
            'applicant_identity_id' => 'treasurer_1',
            'co_sign_trustee_id' => 'treasurer_1',
            'reviewer_id' => 'reviewer_other',
            'countersigner_id' => (string) $reviewer->id,
        ], $headers)->assertStatus(422)->assertJsonPath('error.code', 'second_trustee_cosign_required');

        // Distinct co-sign trustee + distinct countersigner -> authorized with 48h cooling-off.
        $ok = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/institutions/temple_bank_01/bank-mandate', [
            'applicant_identity_id' => 'treasurer_1',
            'co_sign_trustee_id' => 'president_2',
            'reviewer_id' => 'reviewer_other',
            'countersigner_id' => (string) $reviewer->id,
        ], $headers)->assertOk()->assertJsonPath('data.authorized', true);

        $this->assertNotEmpty($ok->json('data.cooling_off_until'));
    }
}

