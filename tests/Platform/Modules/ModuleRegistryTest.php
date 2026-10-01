<?php

namespace Tests\Platform\Modules;

use App\Platform\Modules\ModuleManifest;
use RuntimeException;
use Tests\KernelTestCase;

/**
 * Module registry discovery, validation and dependency resolution
 * (doc 01 §2, doc 15 M0 exit criteria).
 */
final class ModuleRegistryTest extends KernelTestCase
{
    public function test_kernel_module_is_always_registered_first(): void
    {
        $registry = $this->registryForFixture('empty');

        $modules = $registry->modules();

        $this->assertCount(1, $modules);
        $this->assertSame('platform', $modules[0]->code);
        $this->assertTrue($modules[0]->kernel);
    }

    public function test_discovers_modules_and_resolves_dependency_order(): void
    {
        $registry = $this->registryForFixture('discovery');

        $codes = array_map(
            static fn (ModuleManifest $module): string => $module->code,
            $registry->modules(),
        );

        // Kernel first; bravo (priority 100) depends on alpha (priority 200),
        // so topological order must place alpha before bravo.
        $this->assertSame(['platform', 'alpha', 'bravo'], $codes);
    }

    public function test_exposes_permissions_scopes_and_events(): void
    {
        $registry = $this->registryForFixture('discovery');

        $permissions = $registry->permissions();

        $this->assertArrayHasKey('alpha:widget:view', $permissions);
        $this->assertTrue($permissions['alpha:widget:approve']['sensitive']);
        $this->assertSame('alpha', $permissions['alpha:widget:create']['module_code']);

        // Kernel manifest entries (config/platform.php kernel_module) are
        // first-class declarations: the registry exposes them alongside
        // discovered modules (T-0K01-01).
        $this->assertArrayHasKey('platform:policy:view', $permissions);
        $this->assertArrayHasKey('platform:policy:configure', $permissions);
        $this->assertTrue($permissions['platform:policy:configure']['sensitive']);
        // T-0K01-02: the restricted explain capability is declared by the
        // kernel manifest and exposed by the registry.
        $this->assertArrayHasKey('platform:authz:explain', $permissions);
        $this->assertTrue($permissions['platform:authz:explain']['sensitive']);

        $this->assertSame(['alpha:widget:approve'], $registry->scopes());
        $this->assertSame(
            [
                'alpha.widget.created',
                'platform.policy.created',
                'platform.policy.status_changed',
                'platform.policy.updated',
            ],
            $registry->events(),
        );
    }

    public function test_find_and_has(): void
    {
        $registry = $this->registryForFixture('discovery');

        $this->assertTrue($registry->has('alpha'));
        $this->assertFalse($registry->has('missing'));
        $this->assertNull($registry->find('missing'));
        $this->assertSame('Alpha', $registry->find('alpha')->name);
    }

    public function test_dependency_cycle_is_a_boot_error(): void
    {
        $registry = $this->registryForFixture('cycle');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/cycle/i');

        $registry->boot();
    }

    public function test_unknown_dependency_is_a_boot_error(): void
    {
        $registry = $this->registryForFixture('unknown-dep');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/unknown module/');

        $registry->boot();
    }

    public function test_duplicate_module_code_is_a_boot_error(): void
    {
        $registry = $this->registryForFixture('kernel-duplicate');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/duplicate module code/i');

        $registry->boot();
    }

    public function test_module_without_feature_flag_is_enabled_by_default(): void
    {
        $registry = $this->registryForFixture('discovery');

        $bravo = $registry->find('bravo');

        $this->assertNotNull($bravo);
        $this->assertTrue($registry->enabled($bravo));
    }
}
