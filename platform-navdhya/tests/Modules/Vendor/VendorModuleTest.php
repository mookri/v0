<?php

namespace Tests\Modules\Vendor;

use Tests\TestCase;

/**
 * Vendor module shape test (P12b): the registry mounts the routes,
 * module.json is valid, and provider exists — partner routes carry the
 * partner scope, admin verbs capability + step-up gates.
 */
final class VendorModuleTest extends TestCase
{
    public function test_module_has_manifest_and_provider(): void
    {
        $this->assertFileExists(app_path('Modules/Vendor/module.json'));
        $this->assertFileExists(app_path('Modules/Vendor/ModuleServiceProvider.php'));
    }

    public function test_module_manifest_is_valid(): void
    {
        $path = app_path('Modules/Vendor/module.json');
        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('vendor', $manifest['code']);
        $this->assertSame('Vendor Management', $manifest['name']);
        $this->assertSame(['onboarding', 'catalog'], $manifest['dependencies']);
        $this->assertIsArray($manifest['permissions']);
        $this->assertIsArray($manifest['scopes']);
        $this->assertIsArray($manifest['events']);

        foreach ($manifest['events'] as $event) {
            $this->assertStringStartsWith('vendor.', $event['name']);
            $this->assertArrayHasKey('schema', $event);
        }
    }

    public function test_partner_and_admin_routes_mount_with_gates(): void
    {
        $routes = app('router')->getRoutes();

        // The registry mounts module routes with a "<code>." name prefix.
        $partnerStore = $routes->getByName('vendor.partner.vendors.store');
        $this->assertNotNull($partnerStore);
        $this->assertStringStartsWith('api/v1/vendor/partner/vendors', $partnerStore->uri());

        $ordersAdvance = $routes->getByName('vendor.partner.vendors.orders.advance');
        $this->assertNotNull($ordersAdvance);
        $this->assertStringContainsString(':advance', $ordersAdvance->uri());

        $verify = $routes->getByName('vendor.admin.vendors.verify');
        $this->assertNotNull($verify);
        $middleware = $verify->gatherMiddleware();
        $this->assertContains('capability:vendor:profile:manage', $middleware);
        $this->assertContains('stepup:2', $middleware);

        $adminIndex = $routes->getByName('vendor.admin.vendors.index');
        $this->assertNotNull($adminIndex);
        $this->assertContains('capability:vendor:profile:view', $adminIndex->gatherMiddleware());
    }
}
