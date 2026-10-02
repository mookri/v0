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

    public function test_oauth_routes_stay_off_until_p2(): void
    {
        // Stock routes unregistered: the token endpoint is not reachable
        // (no client could complete a flow anyway — fail closed twice).
        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'no-such-client',
            'client_secret' => 'nope',
        ])->assertNotFound()->assertJsonPath('error.code', 'not_found');

        $this->get('/oauth/authorize')->assertNotFound();
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
