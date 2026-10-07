<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TenantIntegrationTestCase;

/**
 * Admin read surfaces (plan slice 2): the Blade administration pages are
 * shells over the existing kernel admin APIs. Page shells gate on the web
 * session alone (the fetches gate client-side), and the API reads themselves
 * must reach the same capabilities that guard token clients — session
 * cookie in, fail-closed authorization_denied out without the grant.
 */
final class AdminReadSurfacesTest extends TenantIntegrationTestCase
{
    private const PAGES = [
        '/platform/policies',
        '/platform/roles',
        '/platform/sod-rules',
        '/platform/users',
    ];

    public function test_guests_are_redirected_from_every_admin_page(): void
    {
        foreach (self::PAGES as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_member_gets_each_list_shell_with_workspace_context(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $mounts = [
            '/platform/policies' => ['policy-rows', 'policy-load-more'],
            '/platform/roles' => ['role-rows'],
            '/platform/sod-rules' => ['sod-rows', 'sod-load-more'],
            '/platform/users' => ['user-rows'],
        ];

        foreach ($mounts as $path => $ids) {
            $response = $this->actingAs($user)->get($path);

            $response->assertOk();
            $response->assertSee('name="api-base" content="/api/v1"', false);
            $response->assertSee('name="api-tenant" content="'.$tenant->getKey().'"', false);

            foreach ($ids as $id) {
                $response->assertSee('id="'.$id.'"', false);
            }
        }
    }

    public function test_session_cookie_reads_policies_with_the_view_capability(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $membership = $this->makeMembership($tenant, $user);
        $this->grantCapabilities($tenant, $membership, ['platform:policy:view']);

        [$name, $value] = $this->loginSessionFor($user);

        $this->withUnencryptedCookie($name, $value)
            ->getJson('/api/v1/policies?limit=5', $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['request_id', 'page' => ['has_more', 'next_cursor']]]);
    }

    public function test_session_cookie_is_denied_policies_without_the_capability(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        [$name, $value] = $this->loginSessionFor($user);

        $this->withUnencryptedCookie($name, $value)
            ->getJson('/api/v1/policies', $this->tenantHeaders($tenant))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'authorization_denied');
    }

    public function test_session_cookie_reads_users_with_the_view_capability(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $membership = $this->makeMembership($tenant, $user);
        $this->grantCapabilities($tenant, $membership, ['platform:user:view']);

        [$name, $value] = $this->loginSessionFor($user);

        $this->withUnencryptedCookie($name, $value)
            ->getJson("/api/v1/platform/users?tenant_id={$tenant->getKey()}", $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonFragment(['email' => $user->email]);
    }

    public function test_session_cookie_reads_roles_for_the_workspace(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        [$name, $value] = $this->loginSessionFor($user);

        $this->withUnencryptedCookie($name, $value)
            ->getJson("/api/v1/platform/roles?tenant_id={$tenant->getKey()}", $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['request_id']]);
    }
}
