<?php

namespace Tests\Feature\Communication;

use App\Modules\Customers\Models\NotificationDelivery;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM02-04:
 * Proves that the delivery:reconcile artisan command inspects stuck delivery records,
 * marks reconciliation timestamps, and transitions stale rows (>24h) to EXPIRED.
 */
final class DeliveryReconcileCommandTest extends TenantIntegrationTestCase
{
    public function test_reconcile_command_inspects_and_expires_stale_deliveries(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        // 1. Fresh row (5 mins old) — should NOT be touched
        $fresh = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 4001,
            'kind' => 'booking_confirmed',
            'category' => 'BOOKING',
            'channel' => 'WHATSAPP',
            'provider' => 'whatsapp_cloud',
            'status' => NotificationDelivery::STATUS_SENT,
            'dedup_key' => 'dedup_reconcile_fresh_001',
            'provider_message_id' => 'wamid.fresh.001',
            'created_at' => CarbonImmutable::now()->subMinutes(5),
        ]);

        // 2. Intermediate stuck row (30 mins old) — should be inspected, reconciled_at stamped
        $intermediate = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 4002,
            'kind' => 'booking_confirmed',
            'category' => 'BOOKING',
            'channel' => 'WHATSAPP',
            'provider' => 'whatsapp_cloud',
            'status' => NotificationDelivery::STATUS_DELIVERY_UNKNOWN,
            'dedup_key' => 'dedup_reconcile_unknown_002',
            'provider_message_id' => 'wamid.unknown.002',
        ]);
        $intermediate->timestamps = false;
        $intermediate->created_at = CarbonImmutable::now()->subMinutes(30);
        $intermediate->save();

        // 3. Abandoned stale row (26 hours old) — should be transitioned to EXPIRED
        $stale = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 4003,
            'kind' => 'panchang_alert',
            'category' => 'REMINDER',
            'channel' => 'SMS',
            'provider' => 'msg91',
            'status' => NotificationDelivery::STATUS_SENT,
            'dedup_key' => 'dedup_reconcile_stale_003',
            'provider_message_id' => 'msg91_stale_003',
        ]);
        $stale->timestamps = false;
        $stale->created_at = CarbonImmutable::now()->subHours(26);
        $stale->save();

        $this->artisan('delivery:reconcile', [
            '--threshold-minutes' => 15,
            '--expire-hours' => 24,
        ])->assertExitCode(0);

        $fresh->refresh();
        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        $this->assertNull($fresh->reconciled_at);

        $intermediate->refresh();
        $this->assertSame(NotificationDelivery::STATUS_DELIVERY_UNKNOWN, $intermediate->status);
        $this->assertNotNull($intermediate->reconciled_at);

        $stale->refresh();
        $this->assertSame(NotificationDelivery::STATUS_EXPIRED, $stale->status);
    }
}
