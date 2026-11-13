<?php

namespace Tests\Feature\Verification;

use App\Domain\Verification\Exceptions\PayoutEligibilityException;
use App\Domain\Verification\Fiscal\DisabledTaxRegistrationVerifier;
use App\Domain\Verification\Fiscal\FiscalIntakeService;
use App\Domain\Verification\Fiscal\FreezePayoutOnDiscrepancyListener;
use App\Domain\Verification\Fiscal\GstinStructuralValidator;
use App\Domain\Verification\Fiscal\ManualBankAccountVerifier;
use App\Domain\Verification\Identity\NameMatchBand;
use App\Domain\Verification\Identity\NameMatcher;
use App\Domain\Verification\Models\VerificationBankInstrument;
use App\Modules\Finance\Domain\MakerCheckerPayoutService;
use App\Modules\Finance\Models\Payout;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * Phase 6 (E-TR-05): Structural GSTIN, non-GST vendor path (T-09), Indic NameMatcher (R-08),
 * encrypted bank instruments (T-10/T-11), Finance payout invariant, and legacy JSON cleanup.
 */
final class PhaseSixFinancialIdentityTest extends TenantIntegrationTestCase
{
    public function test_gstin_structural_validator_produces_per_position_reason_codes_and_validates_mod36_checksum(): void
    {
        $validator = new GstinStructuralValidator();

        $this->assertSame(['INVALID_LENGTH_OR_CHARS'], $validator->validate('SHORT')['reason_codes']);
        $this->assertSame(['INVALID_STATE_CODE', 'INVALID_CHECK_DIGIT'], $validator->validate('98AABCT1234F1ZM')['reason_codes']);
        $this->assertContains('INVALID_EMBEDDED_PAN', $validator->validate('29ABXDE1234F1ZM')['reason_codes']);
        $this->assertContains('INVALID_ENTITY_COUNT', $validator->validate('29AABCT1234F0ZM')['reason_codes']);
        $this->assertContains('INVALID_DEFAULT_Z_CHAR', $validator->validate('29AABCT1234F1AM')['reason_codes']);
        $this->assertSame(['INVALID_CHECK_DIGIT'], $validator->validate('29AABCT1234F1Z5')['reason_codes']);

        $valid = $validator->validate('29AABCT1234F1ZM');
        $this->assertTrue($valid['valid']);
        $this->assertSame('29', $valid['state_code']);
    }

    public function test_non_gst_exempt_vendor_completes_business_step_cleanly(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $app = $this->actingAs($user)->postJson('/api/v1/onboarding/applications', ['track' => 'partner-vendor'], $headers)
            ->assertCreated()
            ->json('data.id');

        $res = $this->actingAs($user)->putJson("/api/v1/onboarding/applications/{$app}/step/business", [
            'value' => [
                'gst_status' => 'exempt_below_threshold',
                'legal_name' => 'Sharma Puja Samagri Kendra',
                'bankAccount' => '123456789012',
                'ifsc' => 'SBIN0001234',
            ],
        ], $headers);

        $res->assertOk()->assertJsonPath('data.saved', true);
    }

    public function test_indic_name_matcher_reuses_transliteration_and_remains_advisory_only(): void
    {
        $matcher = app(NameMatcher::class);

        $devanagari = $matcher->compare('Pt. राम शास्त्री', 'Shri Rama Shastri');
        $this->assertSame(NameMatchBand::EXACT_NORMALIZED, $devanagari->band);
        $this->assertTrue($devanagari->advisoryOnly);

        $reordered = $matcher->compare('Swami Vivekananda Sharma', 'Sharma Vivekananda');
        $this->assertSame(NameMatchBand::EXACT_NORMALIZED, $reordered->band);

        $initials = $matcher->compare('K. Narayanan Iyer', 'Krishnan Narayanan Iyer');
        $this->assertContains($initials->band, [NameMatchBand::EXACT_NORMALIZED, NameMatchBand::MINOR_VARIANT_REVIEW]);

        $mismatch = $matcher->compare('Rajesh Kumar Verma', 'completely different entity');
        $this->assertSame(NameMatchBand::SUBSTANTIVE_MISMATCH_ESCALATE, $mismatch->band);
        $this->assertTrue($mismatch->advisoryOnly);
    }

    public function test_draft_step_encrypts_secrets_and_migrates_legacy_evidence_json(): void
    {
        $tenant = $this->makeTenant();
        [$reviewer, $reviewerMembership] = $this->actingAsMembership($tenant, ['onboarding:review:view']);
        $headers = $this->tenantHeaders($tenant);

        $started = $this->actingAs($reviewer)->postJson('/api/v1/onboarding/applications', ['track' => 'partner-vendor'], $headers)
            ->assertCreated();
        $appId = $started->json('data.id');

        // Put business step with raw GSTIN + bank account + IFSC.
        $this->actingAs($reviewer)->putJson("/api/v1/onboarding/applications/{$appId}/step/business", [
            'value' => [
                'gst_status' => 'registered',
                'gstin' => '29AABCT1234F1ZM',
                'legal_name' => 'Omkar Agarbatti Works',
                'bankAccount' => '998877665544',
                'ifsc' => 'HDFC0001234',
                'account_holder_name' => 'Omkar Agarbatti Works',
            ],
        ], $headers)->assertOk();

        $app = PartnerApplication::withoutGlobalScopes()->findOrFail($appId);
        $bizEvidence = $app->evidence['business'];

        // Assert zero raw secrets stripped into evidence JSON (T-10).
        $this->assertArrayNotHasKey('gstin', $bizEvidence);
        $this->assertArrayNotHasKey('bankAccount', $bizEvidence);
        $this->assertArrayNotHasKey('ifsc', $bizEvidence);
        $this->assertSame('5544', substr((string) $bizEvidence['account_last4'], -4));
        $this->assertNotEmpty($bizEvidence['tax_registration_id']);
        $this->assertNotEmpty($bizEvidence['bank_instrument_id']);

        // Masked API endpoint strips secrets and displays ••••5544.
        $this->actingAs($reviewer)->getJson("/api/v1/onboarding/fiscal/bank-instruments/{$bizEvidence['bank_instrument_id']}", $headers)
            ->assertOk()
            ->assertJsonPath('data.instrument.account_masked', '••••5544')
            ->assertJsonPath('data.instrument.ifsc_masked', 'HDFC0••••••');

        // Insert a legacy unencrypted row & verify verification:migrate-legacy-fiscal-json scrubs it cleanly.
        $legacyId = (string) \Illuminate\Support\Str::ulid();
        DB::connection('tenant_shared')->table('partner_applications')->insert([
            'id' => $legacyId,
            'tenant_id' => $tenant->id,
            'identity_id' => (string) $reviewer->id,
            'track' => 'partner-vendor',
            'track_version' => 'partner-vendor-v1',
            'status' => 'DRAFT',
            'market' => 'IN',
            'evidence' => json_encode([
                'identity' => ['name' => 'Legacy Trader'],
                'business' => ['gstin' => '29AABCT1234F1ZM', 'bankAccount' => '112233445566', 'ifsc' => 'ICIC0000101'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->resolveContext($tenant, $reviewerMembership);
        Artisan::call('verification:migrate-legacy-fiscal-json');
        $this->clearContext();

        $scrubbedRow = DB::connection('tenant_shared')->table('partner_applications')->where('id', $legacyId)->first();
        $decoded = json_decode((string) $scrubbedRow->evidence, true);
        $this->assertArrayNotHasKey('gstin', $decoded['business']);
        $this->assertArrayNotHasKey('bankAccount', $decoded['business']);
        $this->assertStringNotContainsString('112233445566', (string) $scrubbedRow->evidence);
        $this->assertSame('5566', $decoded['business']['account_last4']);
    }

    public function test_finance_payout_ownership_invariant_blocks_unsafe_instruments_and_allows_verified(): void
    {
        $tenant = $this->makeTenant();
        [$checker, $checkerMembership] = $this->actingAsMembership($tenant);
        $this->resolveContext($tenant, $checkerMembership);

        $intake = app(FiscalIntakeService::class);
        $payoutService = app(MakerCheckerPayoutService::class);

        // Create pending bank instrument for pandit_pay_01.
        $pendingRes = $intake->recordBankInstrument(
            tenantId: $tenant->id,
            subjectType: 'pandit',
            subjectId: 'pandit_pay_01',
            accountNumber: '123456789012',
            ifsc: 'SBIN0000456',
            accountHolderName: 'Pt. Harish Joshi',
            declaredEntityName: 'Harish Joshi',
            coolingOffUntil: now()->utc()->addHours(24),
        );
        $instId = (string) $pendingRes['instrument']->getKey();

        $payout = $payoutService->initiatePayout($tenant->id, 'pandit_pay_01', 50000, 'INR', 'maker_1');

        // 1. Fails during cooling-off window.
        try {
            $payoutService->approvePayout($tenant->id, (string) $payout->getKey(), 'checker_1', $instId);
            $this->fail('Expected cooling-off block.');
        } catch (PayoutEligibilityException $e) {
            $this->assertSame('payout_instrument_cooling_off', $e->errorCode);
        }

        // Lift cooling-off -> still fails because instrument is 'pending'.
        VerificationBankInstrument::withoutGlobalScopes()->where('id', $instId)->update(['cooling_off_until' => now()->utc()->subMinute()]);
        try {
            $payoutService->approvePayout($tenant->id, (string) $payout->getKey(), 'checker_1', $instId);
            $this->fail('Expected pending instrument block.');
        } catch (PayoutEligibilityException $e) {
            $this->assertSame('payout_instrument_unverified', $e->errorCode);
        }

        // Verify manually -> grants financial claim -> disbursement approval succeeds!
        $intake->verifyBankInstrumentManually($tenant->id, $instId, 'reviewer_1', 'checker_admin');
        $approved = $payoutService->approvePayout($tenant->id, (string) $payout->getKey(), 'checker_1', $instId);
        $this->assertSame(Payout::STATUS_APPROVED, $approved->status);

        // 2. Wrong beneficiary ID mismatch fails closed.
        $otherPayout = $payoutService->initiatePayout($tenant->id, 'other_beneficiary', 40000, 'INR', 'maker_1');
        try {
            $payoutService->approvePayout($tenant->id, (string) $otherPayout->getKey(), 'checker_1', $instId);
            $this->fail('Expected beneficiary mismatch block.');
        } catch (PayoutEligibilityException $e) {
            $this->assertSame('payout_beneficiary_mismatch', $e->errorCode);
        }

        // 3. Discrepancy listener freezes instrument & blocks subsequent payouts.
        app(FreezePayoutOnDiscrepancyListener::class)->handle($tenant->id, 'pandit', 'pandit_pay_01', 'auditor_1', 'NAME_MISMATCH_INVESTIGATION');
        $thirdPayout = $payoutService->initiatePayout($tenant->id, 'pandit_pay_01', 25000, 'INR', 'maker_1');
        try {
            $payoutService->approvePayout($tenant->id, (string) $thirdPayout->getKey(), 'checker_1', $instId);
            $this->fail('Expected frozen instrument block.');
        } catch (PayoutEligibilityException $e) {
            $this->assertSame('payout_instrument_frozen', $e->errorCode);
        }

        // 4. Institutional beneficiary (temple) with individual account requires policy exception note.
        $templeInst = $intake->recordBankInstrument(
            tenantId: $tenant->id,
            subjectType: 'temple',
            subjectId: 'temple_pay_01',
            accountNumber: '223344556677',
            ifsc: 'ICIC0000456',
            accountHolderName: 'Trustee Individual',
            declaredEntityName: 'Trustee Individual',
            accountType: 'individual',
        )['instrument'];
        $intake->verifyBankInstrumentManually($tenant->id, (string) $templeInst->getKey(), 'reviewer_1', 'countersign_1', null);

        $templePayout = $payoutService->initiatePayout($tenant->id, 'temple_pay_01', 90000, 'INR', 'maker_1');
        try {
            $payoutService->approvePayout($tenant->id, (string) $templePayout->getKey(), 'checker_1', (string) $templeInst->getKey());
            $this->fail('Expected policy exception requirement.');
        } catch (PayoutEligibilityException $e) {
            $this->assertSame('institutional_individual_account_exception_required', $e->errorCode);
        }

        $this->clearContext();
    }

    public function test_vendor_supports_rejected_state_and_manual_ports_never_claim_penny_drop(): void
    {
        $this->assertSame('REJECTED', Vendor::STATUS_REJECTED);
        $this->assertSame('REJECTED', Vendor::VERIFICATION_REJECTED);

        $taxCheck = app(DisabledTaxRegistrationVerifier::class)->verifyGstin('t1', '29AABCT1234F1ZM');
        $this->assertSame('native', $taxCheck['mode']);

        $bankCheck = app(ManualBankAccountVerifier::class)->verifyAccount('t1', '12345678901', 'SBIN0001234', 'Rama Shastri');
        $this->assertSame('manual', $bankCheck['mode']);
        $this->assertSame('manual_review', $bankCheck['status']);
    }
}
