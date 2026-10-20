<?php

namespace Tests\Platform\Authz;

use Tests\TenantIntegrationTestCase;

/**
 * Step-up gate matrix (SEC-004, `stepup:2`): sensitive operations demand
 * a fresh MFA proof — unenrolled, stale, and machine callers all 403
 * with `step_up_required`, distinguished by message.
 */
final class StepUpTest extends TenantIntegrationTestCase
{
    public function test_unenrolled_stale_and_fresh(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:apikey:view', 'platform:apikey:manage']);
        $authed = fn () => $this->actingAs($issuer);
        $issue = fn () => $authed()->postJson('/api/v1/platform/api-keys', [
            'tenant_id' => $tid, 'name' => 'step-up probe',
        ]);

        // No MFA enrolled → 403 telling the client to enroll first.
        $issue()->assertForbidden()->assertJsonPath('error.code', 'step_up_required');

        // MFA enabled but no fresh proof → 403.
        $issuer->forceFill(['mfa_enabled' => true])->save();
        $issue()->assertForbidden()->assertJsonPath('error.code', 'step_up_required');

        // Stale proof (16 min) → 403.
        $this->stepUp($issuer, 16);
        $issue()->assertForbidden()->assertJsonPath('error.code', 'step_up_required');

        // Fresh proof → through.
        $this->stepUp($issuer);
        $issue()->assertCreated();
    }

    public function test_machines_cannot_step_up(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:apikey:view', 'platform:apikey:manage']);
        $this->stepUp($issuer);

        $secret = $this->actingAs($issuer)->postJson('/api/v1/platform/api-keys', [
            'tenant_id' => $tid, 'name' => 'machine',
        ])->assertCreated()->json('data.secret');
        auth()->logout();

        // Machine principals fail at the gate even holding a valid key
        // (spec: client credentials can never satisfy ACR 2).
        $this->postJson('/api/v1/platform/api-keys', ['tenant_id' => $tid, 'name' => 'x'], [
            'Authorization' => "Bearer {$secret}",
        ])->assertForbidden()->assertJsonPath('error.code', 'step_up_required');
    }
}
