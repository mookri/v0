<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Reliability\DeliveryTelemetryMetrics;
use App\Modules\Customers\Models\NotificationDelivery;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM10-02:
 * Operational health metrics and latency telemetry calculations.
 */
final class DeliveryTelemetryMetricsTest extends TenantIntegrationTestCase
{
    public function test_empty_deliveries_returns_zero_metrics(): void
    {
        $metrics = new DeliveryTelemetryMetrics();
        $result = $metrics->calculate();

        $this->assertSame(0, $result['total']);
        $this->assertSame(0.0, $result['delivery_rate_percent']);
        $this->assertSame([], $result['by_channel']);
    }

    public function test_calculates_rates_and_average_latency_accurately(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $now = CarbonImmutable::now();

        // 1. Delivered SMS with 4s latency
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 101,
            'channel' => 'sms',
            'kind' => 'booking_confirmed',
            'status' => NotificationDelivery::STATUS_DELIVERED,
            'sent_at' => $now->subSeconds(10),
            'delivered_at' => $now->subSeconds(6), // diff = 4s
            'dedup_key' => 'dedup_metric_01',
        ]);

        // 2. Delivered WhatsApp with 2s latency
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 102,
            'channel' => 'whatsapp',
            'kind' => 'booking_confirmed',
            'status' => NotificationDelivery::STATUS_DELIVERED,
            'sent_at' => $now->subSeconds(12),
            'delivered_at' => $now->subSeconds(10), // diff = 2s
            'dedup_key' => 'dedup_metric_02',
        ]);

        // 3. Failed Email
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 103,
            'channel' => 'email',
            'kind' => 'marketing',
            'status' => NotificationDelivery::STATUS_FAILED,
            'failed_at' => $now->subSeconds(5),
            'dedup_key' => 'dedup_metric_03',
        ]);

        // 4. Suppressed SMS
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 104,
            'channel' => 'sms',
            'kind' => 'marketing',
            'status' => NotificationDelivery::STATUS_SUPPRESSED,
            'dedup_key' => 'dedup_metric_04',
        ]);

        $metrics = new DeliveryTelemetryMetrics();
        $result = $metrics->calculate($tenantId);

        $this->assertSame(4, $result['total']);
        $this->assertSame(2, $result['by_channel']['sms']);
        $this->assertSame(1, $result['by_channel']['whatsapp']);
        $this->assertSame(1, $result['by_channel']['email']);

        // 2 delivered out of 4 = 50%
        $this->assertEquals(50.0, $result['delivery_rate_percent']);
        // 1 failed out of 4 = 25%
        $this->assertEquals(25.0, $result['failure_rate_percent']);
        // 1 suppressed out of 4 = 25%
        $this->assertEquals(25.0, $result['suppression_rate_percent']);

        // Average latency: (4s + 2s) / 2 = 3.0s
        $this->assertEquals(3.0, $result['average_delivery_latency_seconds']);
    }
}

