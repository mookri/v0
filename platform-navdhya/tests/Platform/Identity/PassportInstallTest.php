<?php

namespace Tests\Platform\Identity;

use Illuminate\Support\Facades\Schema;
use Tests\TenantIntegrationTestCase;

/**
 * Passport P1 (TOKEN-001): dependency installed, keys generated, stock
 * tables migrated, routes OFF (ignoreRoutes until P2 configures clients
 * and grants) — no auth flows touched, no half-wired /oauth/* surface.
 */
final class PassportInstallTest extends TenantIntegrationTestCase
{
    public function test_oauth_tables_exist_on_central(): void
    {
        foreach ([
            'oauth_auth_codes',
            'oauth_access_tokens',
            'oauth_refresh_tokens',
            'oauth_clients',
            'oauth_device_codes',
        ] as $table) {
            $this->assertTrue(
                Schema::connection('central')->hasTable($table),
                "Passport table {$table} is missing on central."
            );
        }
    }

    public function test_oauth_routes_on_with_consent(): void
    {
        // P2 turned the routes on with the consent screen: guests
        // redirect to login (never a 500), and the token endpoint
        // answers protocol errors as JSON.
        $this->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => 'no-such-client',
            'redirect_uri' => 'https://example.test/callback',
        ]))->assertUnauthorized();

        $this->postJson('/oauth/token', ['grant_type' => 'password'])
            ->assertStatus(400)->assertJsonPath('error', 'unsupported_grant_type');
    }

    public function test_hmac_bridge_untouched(): void
    {
        // P1 changes nothing about authentication: the HMAC middleware
        // still denies guests with the stable code (full suite re-verifies
        // the rest).
        $this->postJson('/api/v1/platform/api-keys', ['tenant_id' => 'x', 'name' => 'x'])
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }
}
