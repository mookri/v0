<?php

namespace Tests\Feature\Verification;

use App\Domain\Privacy\Consent\ConsentManager;
use App\Domain\Privacy\Enums\ConsentPurpose;
use App\Domain\Privacy\Incidents\IncidentRecorder;
use App\Domain\Privacy\Models\PrivacyIncident;
use App\Domain\Privacy\Models\PrivacyRetentionPolicy;
use App\Domain\Privacy\Models\VerificationProviderRegistration;
use App\Domain\Privacy\Providers\ProviderResidencyGate;
use App\Domain\Privacy\Support\IdentityFingerprint;
use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Models\VerificationClaim;
use App\Modules\Onboarding\Models\ApplicationDocument;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Api\CodedHttpException;
use App\Platform\Tenancy\TenantScope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantIntegrationTestCase;

/**
 * Tests E-TR-06 / T-TR06-01..T-TR06-05 (Closes T-17):
 * - Granular non-cascading ConsentManager & withdrawal
 * - Retention policy sweep with Storage::delete and legal_hold respect
 * - Partner DSR access, correction & erasure preserving statutory audit & claims
 * - Keyed HMAC IdentityFingerprint & IncidentRecorder
 * - ProviderResidencyGate fail-closed DPA/subprocessor enforcement
 */
final class PhaseThreePrivacyTest extends TenantIntegrationTestCase
{
    private function validJpegBytes(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00" . str_repeat("\x12", 128);
    }

    public function test_granular_consent_is_non_cascading_and_withdrawable_via_api(): void
    {
        $tenant = $this->makeTenant();
        [$partner, $mem] = $this->actingAsMembership($tenant, ['onboarding:consent:manage']);
        $headers = $this->tenantHeaders($tenant);

        $this->actingAs($partner)->postJson('/api/v1/onboarding/me/privacy/consent', [
            'purpose' => ConsentPurpose::IDENTITY_KYC->value,
        ], $headers)->assertCreated()->assertJsonPath('data.granted', true);

        $this->resolveContext($tenant, $mem);
        $consents = app(ConsentManager::class);

        $this->assertTrue($consents->hasActiveConsent((string) $tenant->id, (string) $partner->id, ConsentPurpose::IDENTITY_KYC));
        $this->assertFalse($consents->hasActiveConsent((string) $tenant->id, (string) $partner->id, ConsentPurpose::ANTECEDENT_CHECK));
        $this->assertFalse($consents->hasActiveConsent((string) $tenant->id, (string) $partner->id, ConsentPurpose::QUALIFICATION_OUTREACH));
        $this->clearContext();

        $this->actingAs($partner)->deleteJson('/api/v1/onboarding/me/privacy/consent', [
            'purpose' => ConsentPurpose::IDENTITY_KYC->value,
        ], $headers)->assertOk();

        $this->assertFalse($consents->hasActiveConsent((string) $tenant->id, (string) $partner->id, ConsentPurpose::IDENTITY_KYC));
    }

    public function test_provider_residency_gate_fails_closed_without_dpa(): void
    {
        $gate = app(ProviderResidencyGate::class);

        $blockedUnregistered = false;
        try {
            $gate->assertApproved('karza-gst');
        } catch (CodedHttpException $e) {
            $blockedUnregistered = $e->envelopeCode === 'provider_not_compliant';
        }
        $this->assertTrue($blockedUnregistered);

        VerificationProviderRegistration::query()->create([
            'provider_code' => 'karza-gst',
            'jurisdiction' => 'IN',
            'dpa_executed' => true,
            'dpa_ref' => 'DPA-2026-01',
            'subprocessor_approved' => false,
            'residency_region' => 'ap-south-1',
            'enabled' => true,
        ]);

        $blockedPartial = false;
        try {
            $gate->assertApproved('karza-gst');
        } catch (CodedHttpException) {
            $blockedPartial = true;
        }
        $this->assertTrue($blockedPartial);

        VerificationProviderRegistration::query()->where('provider_code', 'karza-gst')->update(['subprocessor_approved' => true]);
        $approved = $gate->assertApproved('karza-gst');
        $this->assertSame('ap-south-1', $approved->residency_region);
    }

    public function test_retention_purge_command_respects_legal_hold_and_deletes_expired_blobs(): void
    {
        Storage::fake('local');
        $tenant = $this->makeTenant();
        [$partner] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $started = $this->actingAs($partner)->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers);
        $appId = (string) $started->json('data.id');

        $upload = $this->actingAs($partner)->post(
            "/api/v1/onboarding/applications/{$appId}/documents",
            ['kind' => 'id-proof', 'file' => UploadedFile::fake()->createWithContent('id.jpg', $this->validJpegBytes())],
            $headers,
        );
        $docId = (string) $upload->json('data.id');
        $doc = ApplicationDocument::withoutGlobalScope(TenantScope::class)->findOrFail($docId);
        $objectKey = (string) $doc->object_key;
        $this->assertTrue(Storage::disk('local')->exists($objectKey));

        PartnerApplication::withoutGlobalScope(TenantScope::class)->where('id', $appId)->update(['status' => 'REJECTED']);
        ApplicationDocument::withoutGlobalScope(TenantScope::class)->where('id', $docId)->update(['created_at' => now()->utc()->subDays(120)]);

        PrivacyRetentionPolicy::query()->create([
            'dataset_key' => 'rejected_application_documents',
            'retention_days' => 90,
            'legal_hold' => true,
        ]);

        // Under legal hold, blob must survive.
        $this->artisan('privacy:purge-expired', ['--tenant' => $tenant->id])->assertExitCode(0);
        $this->assertTrue(Storage::disk('local')->exists($objectKey));

        // Lift legal hold -> Storage::delete runs.
        PrivacyRetentionPolicy::query()->where('dataset_key', 'rejected_application_documents')->update(['legal_hold' => false]);
        $this->artisan('privacy:purge-expired', ['--tenant' => $tenant->id])->assertExitCode(0);
        $this->assertFalse(Storage::disk('local')->exists($objectKey));
        $this->assertSame('[ERASED]', $doc->fresh()->object_key);
    }

    public function test_dsr_access_correction_and_erasure_preserve_claims_and_audit(): void
    {
        Storage::fake('local');
        $tenant = $this->makeTenant();
        [$partner, $mem] = $this->actingAsMembership($tenant, ['onboarding:subject:request']);
        $headers = $this->tenantHeaders($tenant);

        $started = $this->actingAs($partner)->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers);
        $appId = (string) $started->json('data.id');

        $upload = $this->actingAs($partner)->post(
            "/api/v1/onboarding/applications/{$appId}/documents",
            ['kind' => 'id-proof', 'file' => UploadedFile::fake()->createWithContent('id.jpg', $this->validJpegBytes())],
            $headers,
        );
        $docId = (string) $upload->json('data.id');
        $objectKey = (string) ApplicationDocument::withoutGlobalScope(TenantScope::class)->findOrFail($docId)->object_key;

        $this->resolveContext($tenant, $mem);
        app(ClaimManager::class)->grantClaim([
            'tenant_id' => (string) $tenant->id,
            'subject_type' => 'pandit',
            'subject_id' => (string) $partner->id,
            'application_id' => $appId,
            'claim_kind' => ClaimKind::IDENTITY->value,
            'mode' => EvidenceMode::MANUAL->value,
            'decided_by' => 'rev_1',
        ]);
        $this->clearContext();

        $this->actingAs($partner)->getJson('/api/v1/onboarding/me/privacy/access', $headers)
            ->assertOk()
            ->assertJsonPath('data.subject_identity_id', (string) $partner->id)
            ->assertJsonPath('data.claims_count', 1);

        $this->actingAs($partner)->postJson('/api/v1/onboarding/me/privacy/correction', [
            'reason' => 'Misspelled legal surname',
            'correction_payload' => ['name' => 'Sharma'],
        ], $headers)->assertCreated()->assertJsonPath('data.status', 'received');

        $this->actingAs($partner)->postJson('/api/v1/onboarding/me/privacy/erasure', [
            'reason' => 'Withdrawing from platform',
        ], $headers)->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertFalse(Storage::disk('local')->exists($objectKey));
        $this->assertSame('[ERASED]', ApplicationDocument::withoutGlobalScope(TenantScope::class)->findOrFail($docId)->object_key);
        $this->assertTrue((bool) (PartnerApplication::withoutGlobalScope(TenantScope::class)->findOrFail($appId)->evidence['_erased'] ?? false));
        $this->assertSame(1, VerificationClaim::query()->withoutGlobalScopes()->where('application_id', $appId)->count());

        $this->artisan('verification:integrity', ['--tenant' => (string) $tenant->id, '--skip-documents' => true])
            ->assertExitCode(0);
    }

    public function test_hmac_fingerprint_and_incident_recorder(): void
    {
        $h1 = IdentityFingerprint::hash('pan', ' abcde1234f ');
        $h2 = IdentityFingerprint::hash('pan', 'ABCDE1234F');
        $this->assertSame($h1, $h2);
        $this->assertNotSame(hash('sha256', 'ABCDE1234F'), $h1);

        $incident = app(IncidentRecorder::class)->record(null, 'high', 'unauthorized_access', 'Simulated test breach', 3, 'secops');
        $this->assertInstanceOf(PrivacyIncident::class, $incident);
        $this->assertSame('high', $incident->severity);
    }
}
