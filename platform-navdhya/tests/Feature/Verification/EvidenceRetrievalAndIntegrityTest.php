<?php

namespace Tests\Feature\Verification;

use App\Modules\Onboarding\Models\ApplicationDocument;
use App\Platform\Audit\AuditLog;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Tasks T-TR01-01, T-TR01-02, T-TR01-03 (Closes T-04, T-05 / Gate G-EVID-0):
 * - Signed, capability-checked & audited evidence streaming (no object_key leak)
 * - SHA-256 integrity checking on read & verification:integrity audit chain verifier
 * - Magic-byte polyglot/JS-in-PDF rejection & attachment Content-Disposition for PDFs
 */
final class EvidenceRetrievalAndIntegrityTest extends TenantIntegrationTestCase
{
    private function validJpegBytes(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00" . str_repeat("\x12", 128);
    }

    private function validPdfBytes(): string
    {
        return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    public function test_document_access_enforces_capability_signed_link_and_audit(): void
    {
        Storage::fake('local');
        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $appRes = $this->actingAs($applicant)->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers)->assertCreated();
        $appId = (string) $appRes->json('data.id');

        $upload = $this->actingAs($applicant)->post(
            "/api/v1/onboarding/applications/{$appId}/documents",
            ['kind' => 'certification', 'file' => UploadedFile::fake()->createWithContent('cert.pdf', $this->validPdfBytes())],
            $headers
        )->assertCreated();
        $docId = (string) $upload->json('data.id');
        $this->assertArrayNotHasKey('object_key', $upload->json('data'));

        // 1. Reviewer WITHOUT onboarding:document:access gets 403
        [$viewerWithoutDocCap] = $this->actingAsMembership($tenant, ['onboarding:review:view']);
        $this->actingAs($viewerWithoutDocCap)
            ->getJson("/api/v1/onboarding/documents/{$docId}/access-link", $headers)
            ->assertForbidden();

        // 2. Reviewer WITH capability generates signed URL (no object_key leaked)
        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:view', 'onboarding:document:access']);
        $linkRes = $this->actingAs($reviewer)
            ->getJson("/api/v1/onboarding/documents/{$docId}/access-link", $headers)
            ->assertOk();

        $this->assertArrayNotHasKey('object_key', $linkRes->json('data'));
        $contentUrl = (string) $linkRes->json('data.content_url');
        $this->assertStringContainsString('signature=', $contentUrl);

        // 3. Forged signature is rejected with 403
        $this->actingAs($reviewer)
            ->getJson("/api/v1/onboarding/documents/{$docId}/content?expires=" . (time() + 60) . '&signature=forged', $headers)
            ->assertForbidden();

        // 4. Tenant B reviewer gets 404 (G-TENANT-0)
        $tenantB = $this->makeTenant();
        [$foreignReviewer] = $this->actingAsMembership($tenantB, ['onboarding:review:view', 'onboarding:document:access']);
        $this->actingAs($foreignReviewer)
            ->getJson($contentUrl, $this->tenantHeaders($tenantB))
            ->assertNotFound();

        // 5. Authorized reviewer receives PDF strictly as attachment + audited row
        $stream = $this->actingAs($reviewer)->get($contentUrl, $headers);
        $stream->assertOk();
        $stream->assertHeader('Content-Type', 'application/pdf');
        $stream->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('attachment;', (string) $stream->headers->get('Content-Disposition'));

        $this->assertAuditRecorded($tenant, 'onboarding.document.accessed');
    }

    public function test_tampered_document_blocks_read_and_integrity_command_catches_audit_tamper(): void
    {
        Storage::fake('local');
        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $appId = (string) $this->actingAs($applicant)
            ->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers)
            ->assertCreated()
            ->json('data.id');

        $docId = (string) $this->actingAs($applicant)->post(
            "/api/v1/onboarding/applications/{$appId}/documents",
            ['kind' => 'id-proof', 'file' => UploadedFile::fake()->createWithContent('id.jpg', $this->validJpegBytes())],
            $headers
        )->assertCreated()->json('data.id');

        [$reviewer] = $this->actingAsMembership($tenant, ['onboarding:review:view', 'onboarding:document:access']);
        $this->resolveContext($tenant);
        app(AuditWriter::class)->record('test.chain.entry', ['resource_type' => 'doc', 'resource_id' => $docId]);
        $this->clearContext();

        // Clean baseline passes verification:integrity
        $this->artisan('verification:integrity')->assertExitCode(0);

        // Tamper with stored document bytes on disk
        $this->resolveContext($tenant);
        $doc = ApplicationDocument::query()->findOrFail($docId);
        Storage::disk('local')->put($doc->object_key, 'TAMPERED_BYTES_ATTACK');
        $this->clearContext();

        $linkUrl = (string) $this->actingAs($reviewer)
            ->getJson("/api/v1/onboarding/documents/{$docId}/access-link", $headers)
            ->assertOk()
            ->json('data.content_url');

        // Read fails closed with 409 integrity_mismatch and emits audit event
        $this->actingAs($reviewer)->getJson($linkUrl, $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'integrity_mismatch');

        $this->assertAuditRecorded($tenant, 'onboarding.document.integrity_failed');
        $this->artisan('verification:integrity')->assertExitCode(1);

        // Restore valid bytes then tamper with audit_logs action directly in SQL to verify chain checker
        Storage::disk('local')->put($doc->object_key, $this->validJpegBytes());
        $this->artisan('verification:integrity')->assertExitCode(0);

        $auditRow = AuditLog::withoutGlobalScopes()->where('tenant_id', (string) $tenant->getKey())->firstOrFail();
        AuditLog::withoutGlobalScopes()->where('id', $auditRow->id)->update(['action' => 'tampered.action']);

        $this->artisan('verification:integrity')->assertExitCode(1);
    }

    public function test_magic_byte_validator_rejects_polyglot_and_active_js_pdf(): void
    {
        Storage::fake('local');
        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $appId = (string) $this->actingAs($applicant)
            ->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers)
            ->assertCreated()
            ->json('data.id');

        // 1. HTML script disguised as .jpg
        $fakeImg = UploadedFile::fake()->createWithContent('evil.jpg', '<html><script>alert(1)</script></html>');
        $this->actingAs($applicant)
            ->post("/api/v1/onboarding/applications/{$appId}/documents", ['kind' => 'id-proof', 'file' => $fakeImg], $headers)
            ->assertStatus(422);

        // 2. PDF containing active /JavaScript payload
        $jsPdf = UploadedFile::fake()->createWithContent('js.pdf', "%PDF-1.4\n1 0 obj << /JS (app.alert(1)) >> endobj\n%%EOF");
        $this->actingAs($applicant)
            ->post("/api/v1/onboarding/applications/{$appId}/documents", ['kind' => 'certification', 'file' => $jsPdf], $headers)
            ->assertStatus(422);
    }
}
