<?php

namespace Tests\Feature\Jyotish\Authz;

use App\Platform\Authz\Permissions\JyotishPermissions;
use App\Platform\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TenantIntegrationTestCase;

/**
 * Authorization matrix and capability enforcement test for Jyotish module (T-JY00-11).
 * Verifies AC-T-JY00-11:
 *  - Routes are protected by capability middleware.
 *  - Permissions and scopes match module manifest identically.
 *  - Token lacking jyotish:chart:read receives 403 Forbidden with envelope code forbidden/authorization_denied.
 */
final class AuthorizationMatrixTest extends TenantIntegrationTestCase
{
    public function test_jyotish_manifest_declares_all_canonical_permissions_and_scopes(): void
    {
        /** @var ModuleRegistry $registry */
        $registry = $this->app->make(ModuleRegistry::class);
        $module = $registry->find('jyotish');

        $this->assertNotNull($module, 'Jyotish module must be registered.');

        $declaredCodes = array_map(fn ($p) => $p['code'], $module->permissions);

        foreach (JyotishPermissions::ALL_PERMISSIONS as $permission) {
            $this->assertContains(
                $permission,
                $declaredCodes,
                "Jyotish manifest missing declared permission: {$permission}",
            );
        }

        foreach (JyotishPermissions::ALL_SCOPES as $scope) {
            $this->assertContains(
                $scope,
                $module->scopes,
                "Jyotish manifest missing declared scope: {$scope}",
            );
        }
    }

    public function test_sensitive_ruleset_manage_permission_is_flagged_sensitive(): void
    {
        /** @var ModuleRegistry $registry */
        $registry = $this->app->make(ModuleRegistry::class);
        $module = $registry->find('jyotish');

        $rulesetPerm = null;
        foreach ($module->permissions as $perm) {
            if ($perm['code'] === JyotishPermissions::RULESET_MANAGE) {
                $rulesetPerm = $perm;
                break;
            }
        }

        $this->assertNotNull($rulesetPerm);
        $this->assertTrue(
            $rulesetPerm['sensitive'] ?? false,
            'jyotish:ruleset:manage must be marked sensitive in module manifest.',
        );
    }

    public function test_jyotish_routes_are_capability_gated(): void
    {
        $expectedGates = [
            'jyotish.charts.show' => 'capability:jyotish:chart:read',
            'jyotish.charts.store' => 'capability:jyotish:chart:write',
            'jyotish.calculate' => 'capability:jyotish:calculation:execute',
            'jyotish.rulesets.store' => 'capability:jyotish:ruleset:manage',
        ];

        foreach ($expectedGates as $routeName => $expectedCapability) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} must be registered.");

            $middleware = $route->gatherMiddleware();
            $this->assertContains(
                $expectedCapability,
                $middleware,
                "Route {$routeName} must be protected by middleware {$expectedCapability}",
            );
        }
    }

    public function test_unauthenticated_request_to_chart_detail_is_denied(): void
    {
        $response = $this->getJson('/api/v1/jyotish/charts/ch_sample123');

        // Unauthenticated request hits auth.any -> 401 or 403, never 200 or 404
        $status = $response->status();
        $this->assertTrue(
            in_array($status, [401, 403], true),
            "Unauthenticated request to chart endpoint must be denied with 401 or 403, got {$status}",
        );
    }

    public function test_authenticated_token_lacking_chart_read_receives_403_forbidden(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/jyotish/charts/ch_sample123', $headers)
            ->assertForbidden();

        $this->assertSame('authorization_denied', $response->json('error.code'));
    }
}
