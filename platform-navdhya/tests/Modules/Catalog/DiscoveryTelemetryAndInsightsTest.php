<?php

namespace Tests\Modules\Catalog;

use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Customers\Models\UserEvent;
use App\Modules\Customers\Telemetry\EventRegistry;
use App\Modules\Customers\Telemetry\WarehouseExportService;
use App\Platform\Outbox\OutboxEvent;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class DiscoveryTelemetryAndInsightsTest extends TenantIntegrationTestCase
{
    private string $tenantId;
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $this->tenantId = (string) $tenant->getKey();
        $this->userId = '1001';
    }

    public function test_event_registry_includes_catalog_discovery_events(): void
    {
        $this->assertTrue(EventRegistry::isValid('facet_view'));
        $this->assertTrue(EventRegistry::isValid('price_tier_viewed'));
        $this->assertTrue(EventRegistry::isValid('quote_viewed'));
        $this->assertTrue(EventRegistry::isValid('kit_opt_in'));
        $this->assertTrue(EventRegistry::isValid('view_item'));
        $this->assertTrue(EventRegistry::isValid('search'));
    }

    public function test_warehouse_export_truncates_category_path_to_root_for_privacy(): void
    {
        $event = new UserEvent();
        $event->forceFill([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenantId,
            'user_id' => 9999,
            'event_type' => 'view_item',
            'properties' => [
                'sku' => 'SRV-TEST',
                'category_path' => '/root-rituals/havans/ganapati-homa/',
                'value' => 5000,
            ],
            'context' => ['route_template' => '/rituals/{slug}'],
            'occurred_at' => now(),
        ]);

        $service = new WarehouseExportService('test-hmac-salt');
        $envelope = $service->transformToSignalEnvelope($event);

        $this->assertArrayHasKey('category_path', $envelope['params']);
        // Truncated to root segment only
        $this->assertSame('/root-rituals/', $envelope['params']['category_path']);
        $this->assertSame(5000, $envelope['params']['value']);
    }

    public function test_booking_creation_outbox_carries_telemetry_trace_id(): void
    {
        CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-TRACE-TEST',
            'name' => 'Trace Test Service',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 60,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $traceId = 'trace_' . Str::random(16);
        $useCase = app(CreateReservationDraftUseCase::class);

        $booking = $useCase->execute(
            tenantId: $this->tenantId,
            userId: $this->userId,
            idempotencyKey: (string) Str::uuid(),
            input: [
                'service_sku' => 'SRV-TRACE-TEST',
                'scheduled_at' => '2026-12-05 10:00:00',
                'trace_id' => $traceId,
            ]
        );

        $outbox = OutboxEvent::query()
            ->where('aggregate_type', 'booking')
            ->where('aggregate_id', (string) $booking->getKey())
            ->where('event_type', 'booking.reservation.created')
            ->first();

        $this->assertNotNull($outbox);
        $this->assertSame($traceId, $outbox->payload['trace_id'] ?? null);
    }

    public function test_refresh_catalog_insights_command_executes_cleanly(): void
    {
        CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-INSIGHTS-TEST',
            'name' => 'Insights Test Ritual',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 60,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $this->artisan('catalog:refresh-insights', ['--days' => 7])
            ->assertExitCode(0);
    }
}
