<?php

namespace Tests\Modules\Vendor;

use App\Models\User;
use App\Modules\Vendor\Listeners\VendorLifecycleNotifier;
use App\Platform\Outbox\OutboxHandlers;
use Database\Factories\PartnerApplicationFactory;
use Tests\TenantIntegrationTestCase;

final class VendorRateLimitTest extends TenantIntegrationTestCase
{
    private function ownedVendor($tenant, User $user, array $headers): string
    {
        PartnerApplicationFactory::new()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'identity_id' => (string) $user->getAuthIdentifier(),
            'track' => 'partner-vendor',
            'track_version' => 'partner-vendor-v1',
            'status' => 'APPROVED',
        ]);

        return (string) $this->actingAs($user)->postJson('/api/v1/vendor/partner/vendors', [
            'name' => 'Rate Limit Store '.$user->getKey(),
        ], $headers)->assertCreated()->json('data.id');
    }

    public function test_inventory_adjust_throttled_at_30_per_minute(): void
    {
        $tenant = $this->makeTenant();
        [$owner] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $vendorId = $this->ownedVendor($tenant, $owner, $headers);
        $adjust = '/api/v1/vendor/partner/vendors/'.$vendorId.'/inventory:adjust';

        for ($i = 0; $i < 30; $i++) {
            $resp = $this->actingAs($owner)->postJson($adjust, [
                'sku' => 'SKU-RL',
                'delta' => 1,
            ], $headers);
            $this->assertNotEquals(429, $resp->status(), "Request {$i} should not be throttled");
        }

        // 31st request must be throttled (HTTP 429)
        $resp31 = $this->actingAs($owner)->postJson($adjust, [
            'sku' => 'SKU-RL',
            'delta' => 1,
        ], $headers);

        $this->assertEquals(429, $resp31->status());
    }

    public function test_vendor_events_are_registered_in_outbox_handlers(): void
    {
        /** @var OutboxHandlers $handlers */
        $handlers = $this->app->make(OutboxHandlers::class);

        foreach (VendorLifecycleNotifier::EVENT_TYPES as $eventType) {
            $this->assertTrue(
                $handlers->has($eventType),
                "Outbox handler for '{$eventType}' should be registered."
            );
        }
    }
}
