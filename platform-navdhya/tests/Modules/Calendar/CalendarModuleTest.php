<?php

namespace Tests\Modules\Calendar;

use App\Platform\Modules\Facades\ModuleRegistry;
use Tests\TestCase;

final class CalendarModuleTest extends TestCase
{
    public function test_calendar_module_is_discovered_by_registry(): void
    {
        $module = ModuleRegistry::find('calendar');

        $this->assertNotNull($module, 'Module calendar must be discovered by ModuleRegistry.');
        $this->assertSame('Vedic Calendar & Panchang', $module->name);
        $this->assertSame('0.1.0', $module->version);
    }

    public function test_calendar_permissions_are_declared(): void
    {
        $permissions = ModuleRegistry::permissions();

        $this->assertArrayHasKey('calendar:panchang:read', $permissions);
        $this->assertArrayHasKey('calendar:generation:manage', $permissions);
        $this->assertTrue($permissions['calendar:generation:manage']['sensitive']);
    }
}

