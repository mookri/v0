<?php

namespace Tests\Modules\Jyotish;

use App\Platform\Modules\Facades\ModuleRegistry;
use Tests\TestCase;

final class JyotishModuleTest extends TestCase
{
    public function test_jyotish_module_is_discovered_by_registry(): void
    {
        $module = ModuleRegistry::find('jyotish');

        $this->assertNotNull($module, 'Module jyotish must be discovered by ModuleRegistry.');
        $this->assertSame('Jyotish Calculation Engine', $module->name);
        $this->assertSame('0.1.0', $module->version);
    }

    public function test_jyotish_permissions_are_declared(): void
    {
        $permissions = ModuleRegistry::permissions();

        $this->assertArrayHasKey('jyotish:calculation:execute', $permissions);
        $this->assertArrayHasKey('jyotish:chart:read', $permissions);
        $this->assertArrayHasKey('jyotish:chart:write', $permissions);
        $this->assertArrayHasKey('jyotish:ruleset:manage', $permissions);
        $this->assertTrue($permissions['jyotish:ruleset:manage']['sensitive']);
    }
}

