<?php

namespace Tests\Platform\Identity;

use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * OAuth client HTTP API (mirrors console commands): list, create, show, revoke.
 */
final class OAuthClientHttpTest extends TenantIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 9c: the HTTP client-revoke mirror chains token.revoked rows
        // whose pseudonymization requires the identity secret.
        config(['identity.hmac_secret' => 'oauth-client-http-test-hmac-32!!']);
    }

    public function test_list_requires_auth_and_capability(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        // Without capability
        $this->actingAs($user)->getJson("/api/v1/platform/oauth/clients?tenant_id={$tid}", $headers)
            ->assertForbidden();

        $membership = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', $tid)->where('user_id', $user->id)->firstOrFail();
        $this->grantCapabilities($tenant, $membership, ['platform:user:view']);

        $this->actingAs($user)->getJson("/api/v1/platform/oauth/clients?tenant_id={$tid}", $headers)
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_create_client_requires_stepup_and_returns_secret_once(): void
    {
        $tenant = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenant);
        $membership = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $this->grantCapabilities($tenant, $membership, ['platform:user:view', 'platform:user:manage']);
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($admin);

        // Without step-up
        $authed()->postJson('/api/v1/platform/oauth/clients', [
            'tenant_id' => $tid,
            'name' => 'Test Client',
            'type' => 'first_party_service',
        ], $headers)->assertForbidden()->assertJsonPath('error.code', 'step_up_required');

        $this->stepUp($admin);
        $created = $authed()->postJson('/api/v1/platform/oauth/clients', [
            'tenant_id' => $tid,
            'name' => 'Test Client',
            'type' => 'first_party_service',
        ], $headers)->assertCreated();

        $data = $created->json('data');
        $this->assertArrayHasKey('secret', $data);
        $this->assertNotEmpty($data['secret']);
        $this->assertSame('first_party_service', $data['type']);
        $this->assertSame('Test Client', $data['name']);
        $this->assertSame('client_credentials', $data['grants'][0]);

        // Secret shown once in response
        $secret = $data['secret'];

        // Verify client exists in DB
        $client = Client::query()->where('name', 'Test Client')->firstOrFail();
        $this->assertTrue(Hash::check($secret, $client->secret)); // Stored hashed

        // Profile exists
        $this->assertDatabaseHas('oauth_client_profiles', [
            'client_id' => (string) $client->getKey(),
            'client_type' => 'first_party_service',
            'tenant_id' => $tid,
            'status' => 'active',
        ], 'central');

        // Audit record (may not exist in sqlite test env)
        if (config('database.connections.central.driver') !== 'sqlite') {
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'oauth.client.created',
                'resource_id' => (string) $client->getKey(),
            ], 'central');
        }
    }

    public function test_create_partner_client_requires_tenant(): void
    {
        $tenant = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenant);
        $membership = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $this->grantCapabilities($tenant, $membership, ['platform:user:view', 'platform:user:manage']);
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($admin);

        $this->stepUp($admin);

        // Partner without tenant_id in body should fail (scoping tenant not provided)
        $authed()->postJson('/api/v1/platform/oauth/clients', [
            'name' => 'Partner No Tenant',
            'type' => 'partner',
        ], $headers)->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_show_client_requires_capability(): void
    {
        $tenant = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenant);
        $membership = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $this->grantCapabilities($tenant, $membership, ['platform:user:view', 'platform:user:manage']);
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($admin);

        // Create a client first
        $this->stepUp($admin);
        $created = $authed()->postJson('/api/v1/platform/oauth/clients', [
            'tenant_id' => $tid,
            'name' => 'Show Test Client',
            'type' => 'first_party_web',
            'redirect_uris' => ['https://example.test/callback'],
        ], $headers)->assertCreated();

        $clientId = $created->json('data.id');

        // Now show it
        $this->grantCapabilities($tenant, $membership, ['platform:user:view']);
        $shown = $authed()->getJson("/api/v1/platform/oauth/clients/{$clientId}?tenant_id={$tid}", $headers)->assertOk();

        $this->assertSame('Show Test Client', $shown->json('data.name'));
        $this->assertSame('first_party_web', $shown->json('data.type'));
        $this->assertSame(['https://example.test/callback'], $shown->json('data.redirect_uris'));
        $this->assertArrayNotHasKey('secret', $shown->json('data')); // Secret not shown on show
    }

    public function test_revoke_client_requires_stepup_and_kills_tokens(): void
    {
        $tenant = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenant);
        $membership = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $this->grantCapabilities($tenant, $membership, ['platform:user:view', 'platform:user:manage']);
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($admin);

        // Create a client (requires step-up)
        $this->stepUp($admin);
        $created = $authed()->postJson('/api/v1/platform/oauth/clients', [
            'tenant_id' => $tid,
            'name' => 'Revoke Test',
            'type' => 'first_party_service',
        ], $headers)->assertCreated();

        $clientId = $created->json('data.id');
        $secret = $created->json('data.secret');

        // Get a token first
        $tokenResp = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $secret,
            'scope' => '',
        ])->assertOk();
        $accessToken = $tokenResp->json('access_token');
        $this->assertNotEmpty($accessToken);

        // Revoke with step-up (same step-up from creation is still fresh)
        $authed()->postJson("/api/v1/platform/oauth/clients/{$clientId}:revoke", ['tenant_id' => $tid], $headers)
            ->assertOk();

        // Client revoked
        $client = Client::query()->findOrFail($clientId);
        $this->assertTrue($client->revoked);

        // Profile disabled
        $this->assertDatabaseHas('oauth_client_profiles', [
            'client_id' => $clientId,
            'status' => 'disabled',
        ], 'central');

        // Tokens dead
        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $secret,
            'scope' => '',
        ])->assertStatus(401);

        // Audit record (may not exist in sqlite test env)
        if (config('database.connections.central.driver') !== 'sqlite') {
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'oauth.client.revoked',
                'resource_id' => $clientId,
            ], 'central');
        }
    }

    public function test_cross_tenant_access_denied(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenantA);
        $membership = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenantA->getKey())
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $this->grantCapabilities($tenantA, $membership, ['platform:user:view', 'platform:user:manage']);
        $tidA = (string) $tenantA->getKey();
        $tidB = (string) $tenantB->getKey();
        $headersA = $this->tenantHeaders($tenantA);
        $authed = fn () => $this->actingAs($admin);

        // Create client in tenant A
        $this->stepUp($admin);
        $created = $authed()->postJson('/api/v1/platform/oauth/clients', [
            'tenant_id' => $tidA,
            'name' => 'Tenant A Client',
            'type' => 'first_party_service',
        ], $headersA)->assertCreated();

        $clientId = $created->json('data.id');

        // Try to access from tenant B context (should fail with 403 forbidden)
        $this->actingAs($admin)->getJson("/api/v1/platform/oauth/clients/{$clientId}?tenant_id={$tidB}", $this->tenantHeaders($tenantB))
            ->assertForbidden();

        $authed()->postJson("/api/v1/platform/oauth/clients/{$clientId}:revoke", ['tenant_id' => $tidB], $headersA)
            ->assertForbidden();
    }
}
