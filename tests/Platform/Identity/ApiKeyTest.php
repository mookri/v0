<?php

namespace Tests\Platform\Identity;

use Tests\TenantIntegrationTestCase;

/**
 * Machine API keys: issue (plaintext once), metadata-only listing,
 * immediate revoke, rotation with overlap, and key authentication against
 * tenant routes with cross-tenant denial.
 */
final class ApiKeyTest extends TenantIntegrationTestCase
{
    public function test_issue_lists_revoke_and_rotate(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        // Guest checks first: actingAs persists across requests.
        $this->postJson('/api/v1/platform/api-keys', ['tenant_id' => $tid, 'name' => 'x'])
            ->assertUnauthorized();

        [$issuer] = $this->actingAsMembership($tenant, ['platform:apikey:view', 'platform:apikey:manage']);
        $this->stepUp($issuer);
        $authed = fn () => $this->actingAs($issuer);

        $issued = $authed()->postJson('/api/v1/platform/api-keys', [
            'tenant_id' => $tid, 'name' => 'CI integration', 'scopes' => ['catalog:read'],
        ])->assertCreated();
        $secret = $issued->json('data.secret');
        $this->assertStringStartsWith('sk_live_', $secret);
        $keyId = $issued->json('data.id');

        // Plaintext never stored: only the hash persists.
        $this->assertDatabaseMissing('api_keys', ['id' => $keyId, 'secret_hash' => $secret], 'central');

        $listed = $authed()->getJson("/api/v1/platform/api-keys?tenant_id={$tid}")->assertOk();
        $this->assertSame([$issued->json('data.prefix')], collect($listed->json('data'))->pluck('prefix')->all());
        $this->assertArrayNotHasKey('secret', $listed->json('data.0'));
        $this->assertArrayNotHasKey('secret_hash', $listed->json('data.0'));

        $rotated = $authed()->postJson("/api/v1/platform/api-keys/{$keyId}:rotate", ['tenant_id' => $tid])->assertCreated();
        $this->assertNotSame($secret, $rotated->json('data.secret'));
        $this->assertSame($keyId, $rotated->json('data.supersedes'));

        $authed()->postJson("/api/v1/platform/api-keys/{$keyId}:revoke", ['tenant_id' => $tid])->assertOk();
    }

    public function test_key_authenticates_tenant_routes_and_revoke_bites(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:apikey:view', 'platform:apikey:manage']);
        $tid = (string) $tenant->getKey();
        $this->stepUp($issuer);

        $issued = $this->actingAs($issuer)->postJson('/api/v1/platform/api-keys', [
            'tenant_id' => $tid, 'name' => 'Catalog reader', 'scopes' => ['catalog:read'],
        ])->assertCreated();
        $secret = $issued->json('data.secret');
        $keyId = $issued->json('data.id');

        // Drop session auth: subsequent calls must prove KEY auth, not the
        // leaked actingAs session (same persistence trap as guest checks).
        auth()->logout();
        $headers = ['Authorization' => "Bearer {$secret}", 'X-Tenant-Id' => $tid];

        // Key reaches an ownership-gated read.
        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();

        // Successful key auth stamps usage (metadata for rotation hygiene).
        $this->assertDatabaseHas('api_keys', ['id' => $keyId], 'central');
        $this->assertNotNull(
            \App\Platform\Identity\Models\ApiKey::query()->find($keyId)?->last_used_at,
            'key auth must stamp last_used_at'
        );

        // Machine principals administer nothing (explicit 403, not 401).
        $this->postJson('/api/v1/platform/api-keys', ['tenant_id' => $tid, 'name' => 'self-issue'], $headers)
            ->assertForbidden();

        // Cross-tenant header is denied even with a valid key.
        $other = $this->makeTenant();
        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => "Bearer {$secret}",
            'X-Tenant-Id' => (string) $other->getKey(),
        ])->assertStatus(401);

        // Capability-gated writes deny machine actors (no membership grants).
        $this->postJson('/api/v1/catalog/items', ['sku' => 'K', 'name' => 'K'], $headers)
            ->assertForbidden();

        // Revoke bites immediately.
        $this->actingAs($issuer)->postJson("/api/v1/platform/api-keys/{$keyId}:revoke", ['tenant_id' => $tid])->assertOk();
        auth()->logout();
        $this->getJson('/api/v1/catalog/items', $headers)->assertUnauthorized();
    }

    public function test_garbage_and_foreign_keys_deny_identically(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);
        auth()->logout();
        $headers = ['X-Tenant-Id' => (string) $tenant->getKey()];

        $this->getJson('/api/v1/catalog/items', array_merge($headers, ['Authorization' => 'Bearer sk_live_bogusbogus12']))->assertUnauthorized();
        $this->getJson('/api/v1/catalog/items', array_merge($headers, ['Authorization' => 'Bearer notakey']))->assertUnauthorized();
        $this->getJson('/api/v1/catalog/items', $headers)->assertUnauthorized();
    }
}
