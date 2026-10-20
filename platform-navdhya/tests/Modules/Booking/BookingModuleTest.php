<?php

namespace Tests\Modules\Booking;

use App\Platform\Modules\ModuleRegistry;
use Tests\TenantIntegrationTestCase;

/**
 * Booking module skeleton (B1, ADR-005): registry discovery with the
 * lifecycle actions declared. Reservations land with the B5 slice.
 */
final class BookingModuleTest extends TenantIntegrationTestCase
{
    public function test_module_is_discovered_with_lifecycle_actions(): void
    {
        $registry = app(ModuleRegistry::class);

        $this->assertTrue($registry->has('booking'));

        $manifest = $registry->find('booking');
        $this->assertNotNull($manifest);
        $this->assertContains('cancel', $manifest->extraActions);
        $this->assertContains('reschedule', $manifest->extraActions);
    }
}
