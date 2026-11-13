<?php

namespace Tests\Modules\Onboarding;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantIntegrationTestCase;

/**
 * Onboarding draft surface: step PUT validates against the pinned track
 * catalog, status reports documents, uploads store bytes + metadata with
 * ownership enforced throughout.
 */
final class OnboardingDraftTest extends TenantIntegrationTestCase
{
    public function test_step_put_validates_and_persists_evidence(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $started = $this->actingAs($user)->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers);
        $started->assertCreated();
        $id = $started->json('data.id');

        $this->actingAs($user)->putJson("/api/v1/onboarding/applications/{$id}/step/nope", ['value' => 1], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->actingAs($user)->putJson(
            "/api/v1/onboarding/applications/{$id}/step/identity",
            ['value' => ['name' => 'Ravi Shankar', 'phone' => '+919876543210']],
            $headers
        )->assertOk()->assertJsonPath('data.saved', true);

        $status = $this->actingAs($user)->getJson("/api/v1/onboarding/applications/{$id}/status", $headers)->assertOk();
        $status->assertJsonPath('data.status', 'DRAFT');
        $status->assertJsonPath('data.documents', []);
    }

    public function test_document_upload_stores_bytes_and_metadata(): void
    {
        Storage::fake('local');
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $started = $this->actingAs($user)->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers);
        $id = $started->json('data.id');

        $jpegBytes = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00" . str_repeat("\x11", 256);
        $file = UploadedFile::fake()->createWithContent('id.jpg', $jpegBytes);
        $res = $this->actingAs($user)->post(
            "/api/v1/onboarding/applications/{$id}/documents",
            ['kind' => 'id-proof', 'file' => $file],
            $headers
        );
        $res->assertCreated();
        $res->assertJsonPath('data.status', 'PENDING');
        $res->assertJsonPath('data.kind', 'id-proof');
        $doc = \App\Modules\Onboarding\Models\ApplicationDocument::withoutGlobalScope(\App\Platform\Tenancy\TenantScope::class)->findOrFail($res->json('data.id'));
        Storage::disk('local')->assertExists($doc->object_key);
    }
}
