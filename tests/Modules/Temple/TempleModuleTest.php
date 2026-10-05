<?php

namespace Tests\Modules\Temple;

use Tests\TestCase;

/**
 * Temple module shape test (P11a): the registry mounts the routes,
 * module.json is valid, and provider exists.
 */
final class TempleModuleTest extends TestCase
{
    public function test_module_has_manifest_and_provider(): void
    {
        $this->assertFileExists(app_path('Modules/Temple/module.json'));
        $this->assertFileExists(app_path('Modules/Temple/ModuleServiceProvider.php'));
    }

    public function test_module_manifest_is_valid(): void
    {
        $path = app_path('Modules/Temple/module.json');
        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('temple', $manifest['code']);
        $this->assertSame('Temple Management', $manifest['name']);
        $this->assertIsArray($manifest['permissions']);
        $this->assertIsArray($manifest['scopes']);
        $this->assertIsArray($manifest['events']);
    }
}
