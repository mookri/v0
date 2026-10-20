<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Doc 01 §7(d): every module under app/Modules has module.json, a
 * ModuleServiceProvider, and a mirrored tests/Modules/<Module> directory.
 *
 * Doc 01 §1: routes/web.php and routes/api.php carry no business routes —
 * they iterate the module registry and include module route files. The
 * only direct endpoint declarations allowed in web.php are the Velzon
 * admin shell (RoutingController); api.php declares none.
 */
final class ModuleShapeTest extends TestCase
{
    public function test_every_module_has_manifest_provider_and_tests(): void
    {
        $modulesPath = base_path('app/Modules');

        if (! is_dir($modulesPath)) {
            $this->markTestSkipped('No domain modules yet (they land in M5).');
        }

        $directories = glob($modulesPath.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [];

        if ($directories === []) {
            $this->markTestSkipped('No domain modules yet (they land in M5).');
        }

        foreach ($directories as $directory) {
            $module = basename($directory);

            $this->assertFileExists(
                $directory.DIRECTORY_SEPARATOR.'module.json',
                "Module {$module} is missing module.json (doc 01 §3.1).",
            );

            $this->assertFileExists(
                $directory.DIRECTORY_SEPARATOR.'ModuleServiceProvider.php',
                "Module {$module} is missing ModuleServiceProvider.php (doc 01 §2).",
            );

            $this->assertDirectoryExists(
                base_path('tests/Modules/'.$module),
                "Module {$module} has no tests/Modules/{$module} directory (doc 01 §7(d)).",
            );
        }
    }

    public function test_api_route_file_declares_no_direct_endpoints(): void
    {
        $source = (string) file_get_contents(base_path('routes/api.php'));

        $this->assertDoesNotMatchRegularExpression(
            '/Route::(get|post|put|patch|delete|any|resource|apiResource)\s*\(/',
            $source,
            'routes/api.php must not declare endpoints directly; platform and module route files do (doc 01 §1).',
        );
    }

    public function test_web_route_file_declares_only_the_admin_shell(): void
    {
        $source = (string) file_get_contents(base_path('routes/web.php'));

        preg_match_all(
            '/Route::(get|post|put|patch|delete|any|resource|apiResource)\s*\([^;]+\)/s',
            $source,
            $matches,
        );

        foreach ($matches[0] as $declaration) {
            $this->assertStringContainsString(
                'RoutingController::class',
                $declaration,
                'routes/web.php may only declare the Velzon admin shell directly; business routes belong in module Routes/web.php files (doc 01 §1).',
            );
        }
    }
}
