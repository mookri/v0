<?php

namespace Tests\Platform\Authz;

use Tests\TenantIntegrationTestCase;

/**
 * Machine scope enforcement (A-SEC-014-2): ownership reads carrying
 * `scope:*` middleware admit machine principals holding the exact scope
 * (403 `insufficient_scope` otherwise); humans pass through untouched
 * (grants govern); capability-gated writes still default-deny machines.
 */
final class ScopeEnforcementTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: string, 1: string} bearer secret + tenant id
     */
    private function keyWithScopes(string $tenantId, string $issuerId, ?array $scopes): array
    {
        $issuer = \App\Models\User::find($issuerId);
        $this->stepUp($issuer);
        $payload = ['tenant_id' => $tenantId, 'name' => 'scoped-probe'];
        if ($scopes !== null) {
            $payload['scopes'] = $scopes;
        }

        $secret = $this->actingAs($issuer)->postJson('/api/v1/platform/api-keys', $payload)
            ->assertCreated()->json('data.secret');

        auth()->logout();

        return [$secret, $tenantId];
    }

    public function test_scopeless_key_denied_on_scoped_reads(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        [$secret, $tid] = $this->keyWithScopes((string) $tenant->getKey(), (string) $issuer->getAuthIdentifier(), null);
        $headers = ['Authorization' => "Bearer {$secret}", 'X-Tenant-Id' => $tid];

        $this->getJson('/api/v1/catalog/items', $headers)
            ->assertForbidden()->assertJsonPath('error.code', 'insufficient_scope');
        $this->getJson('/api/v1/booking/reservations', $headers)
            ->assertForbidden()->assertJsonPath('error.code', 'insufficient_scope');
    }

    public function test_scopes_gate_per_module(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        [$secret, $tid] = $this->keyWithScopes(
            (string) $tenant->getKey(),
            (string) $issuer->getAuthIdentifier(),
            ['catalog:read']
        );
        $headers = ['Authorization' => "Bearer {$secret}", 'X-Tenant-Id' => $tid];

        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();
        $this->getJson('/api/v1/booking/reservations', $headers)
            ->assertForbidden()->assertJsonPath('error.code', 'insufficient_scope');
    }

    public function test_humans_pass_through_untouched(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->getJson('/api/v1/catalog/items', $this->tenantHeaders($tenant))->assertOk();
        $this->actingAs($user)->getJson('/api/v1/booking/reservations', $this->tenantHeaders($tenant))->assertOk();
    }

    public function test_capability_writes_still_deny_machines(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        [$secret, $tid] = $this->keyWithScopes(
            (string) $tenant->getKey(),
            (string) $issuer->getAuthIdentifier(),
            ['catalog:read', 'catalog:write', 'booking:read']
        );
        $headers = ['Authorization' => "Bearer {$secret}", 'X-Tenant-Id' => $tid];

        // Scopes never substitute membership grants on capability routes.
        $this->postJson('/api/v1/catalog/items', ['sku' => 'K', 'name' => 'K'], $headers)
            ->assertForbidden()->assertJsonPath('error.code', 'authorization_denied');
    }

    public function test_malformed_scopes_rejected_at_issue(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $this->stepUp($issuer);

        $this->actingAs($issuer)->postJson('/api/v1/platform/api-keys', [
            'tenant_id' => (string) $tenant->getKey(), 'name' => 'bad', 'scopes' => ['not a scope'],
        ])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    }
}
