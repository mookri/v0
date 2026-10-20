<?php

namespace Tests\Feature;

use App\Models\User;
use App\Platform\Tenancy\Models\TenantMembership;
use Tests\TenantIntegrationTestCase;

/**
 * Blade pattern page (plan slice 1): the pandit directory is the first
 * live-fetch admin-shell page. It must render the API context meta tags
 * the page scripts need (session-cookie auth on safe reads + default
 * workspace header) and the cursor-loop mount points, and the context
 * must fail closed to an empty workspace without an active membership.
 */
final class PanditDirectoryPageTest extends TenantIntegrationTestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/pandits/directory')->assertRedirect('/login');
    }

    public function test_member_gets_the_live_directory_shell_with_workspace_context(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $response = $this->actingAs($user)->get('/pandits/directory');

        $response->assertOk();
        $response->assertSee('name="api-base" content="/api/v1"', false);
        $response->assertSee('name="api-tenant" content="'.$tenant->getKey().'"', false);
        $response->assertSee('id="pandit-rows"', false);
        $response->assertSee('id="pandit-load-more"', false);
    }

    public function test_member_without_workspace_renders_fail_closed_empty_context(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/pandits/directory');

        $response->assertOk();
        $response->assertSee('name="api-tenant" content=""', false);
    }

    public function test_suspended_membership_does_not_leak_a_workspace(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user, ['state' => TenantMembership::STATE_SUSPENDED]);

        $response = $this->actingAs($user)->get('/pandits/directory');

        $response->assertOk();
        $response->assertSee('name="api-tenant" content=""', false);
    }
}
