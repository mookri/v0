<?php

namespace Tests\Modules\Catalog;

use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Domain\Exceptions\BookingConflictException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use App\Modules\Catalog\Models\CatalogDurationRule;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogServiceKitBinding;
use App\Platform\Outbox\OutboxEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Five-Way Draft Collision Integration Test (F7).
 * Tests the harmony of B3 (draft) + C (temple) + D (kits) + F2 (duration) + F3 (bindings).
 */
final class FiveWayDraftCollisionTest extends TenantIntegrationTestCase
{
    private string $tenantId;
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $this->tenantId = (string) $tenant->getKey();
        $this->userId = '2001';
    }

    public function test_five_way_collision_zone_draft_creation_succeeds_with_all_invariants(): void
    {
        // 1. Service Item
        $service = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-5WAY-MAHA-GANAPATI',
            'name' => 'Maha Ganapati Homa',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 90,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        // 2. F2 Duration Rule: +15m per 2 extra participants
        CatalogDurationRule::create([
            'tenant_id' => $this->tenantId,
            'item_id' => (string) $service->getKey(),
            'scope' => 'PER_PARTICIPANT',
            'scope_key' => 'default',
            'rule' => ['per' => 2, 'add' => 15],
        ]);

        // 3. F3 Samagri Kit Items
        $reqKit = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'KIT-HOMA-BASIC',
            'name' => 'Basic Homa Samagri',
            'item_kind' => CatalogItem::KIND_KIT,
            'base_duration_minutes' => 0,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $incKit = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'KIT-PRASAD-LADDUS',
            'name' => 'Modak Prasad Offerings',
            'item_kind' => CatalogItem::KIND_KIT,
            'base_duration_minutes' => 0,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $optKit = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'KIT-BRASS-DIYAS',
            'name' => 'Consecrated Brass Diyas',
            'item_kind' => CatalogItem::KIND_KIT,
            'base_duration_minutes' => 0,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        // Bindings
        CatalogServiceKitBinding::create([
            'tenant_id' => $this->tenantId,
            'service_item_id' => (string) $service->getKey(),
            'kit_item_id' => (string) $reqKit->getKey(),
            'obligation' => CatalogServiceKitBinding::OBLIGATION_REQUIRED,
            'quantity_per_party' => 1,
            'sort' => 10,
        ]);

        CatalogServiceKitBinding::create([
            'tenant_id' => $this->tenantId,
            'service_item_id' => (string) $service->getKey(),
            'kit_item_id' => (string) $incKit->getKey(),
            'obligation' => CatalogServiceKitBinding::OBLIGATION_INCLUDED,
            'quantity_per_party' => 1,
            'sort' => 20,
        ]);

        CatalogServiceKitBinding::create([
            'tenant_id' => $this->tenantId,
            'service_item_id' => (string) $service->getKey(),
            'kit_item_id' => (string) $optKit->getKey(),
            'obligation' => CatalogServiceKitBinding::OBLIGATION_OPTIONAL,
            'quantity_per_party' => 1,
            'sort' => 30,
        ]);

        $traceId = 'trace_5way_' . Str::random(12);
        $idempotencyKey = (string) Str::uuid();
        $useCase = app(CreateReservationDraftUseCase::class);

        // Execute draft with 3 participants and opt-in to optional kit
        // Expected Duration: 90 + floor((3-1)/2)*15 = 90 + 15 = 105 mins
        $booking = $useCase->execute(
            tenantId: $this->tenantId,
            userId: $this->userId,
            idempotencyKey: $idempotencyKey,
            input: [
                'service_sku' => 'SRV-5WAY-MAHA-GANAPATI',
                'scheduled_at' => '2026-12-15 08:30:00',
                'participants' => 3,
                'package_tier' => 'STANDARD',
                'kit_optins' => ['KIT-BRASS-DIYAS'],
                'trace_id' => $traceId,
            ]
        );

        // Assert Booking Draft state (B3)
        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame('DRAFT', $booking->status);
        $this->assertSame(105, $booking->duration_minutes); // F2 Authority
        $this->assertSame('2026-12-15 08:30:00', $booking->service_start_at?->toDateTimeString());
        $this->assertSame('2026-12-15 10:15:00', $booking->service_end_at?->toDateTimeString());

        // Assert Revision 1 and Lines (F2 & F3)
        $revision = $booking->revisions()->first();
        $this->assertNotNull($revision);
        $this->assertSame(1, $revision->version);

        $lines = BookingLine::query()->where('revision_id', (string) $revision->getKey())->get();
        // 4 lines: 1 service + 1 required kit + 1 included kit + 1 optional kit
        $this->assertCount(4, $lines);

        $serviceLine = $lines->firstWhere('line_kind', 'SERVICE');
        $this->assertNotNull($serviceLine);
        $this->assertSame(105, $serviceLine->server_duration_minutes);
        $this->assertNotNull($serviceLine->duration_rule_snapshot);
        $this->assertSame(105, $serviceLine->duration_rule_snapshot['minutes']);

        // Required kit line (qty = 1 * 3 = 3)
        $reqLine = $lines->firstWhere('sku', 'KIT-HOMA-BASIC');
        $this->assertNotNull($reqLine);
        $this->assertSame(3, $reqLine->quantity);
        $this->assertSame((string) $serviceLine->getKey(), $reqLine->parent_line_id);

        // Included kit line (qty = 1, price_cents = 0)
        $incLine = $lines->firstWhere('sku', 'KIT-PRASAD-LADDUS');
        $this->assertNotNull($incLine);
        $this->assertSame(1, $incLine->quantity);
        $this->assertSame(0, $incLine->price_cents);
        $this->assertSame((string) $serviceLine->getKey(), $incLine->parent_line_id);

        // Optional kit line (opted in, qty = 1)
        $optLine = $lines->firstWhere('sku', 'KIT-BRASS-DIYAS');
        $this->assertNotNull($optLine);
        $this->assertSame(1, $optLine->quantity);
        $this->assertSame((string) $serviceLine->getKey(), $optLine->parent_line_id);

        // Assert Outbox fact carries trace_id (F5)
        $fact = OutboxEvent::query()
            ->where('aggregate_type', 'booking')
            ->where('aggregate_id', (string) $booking->getKey())
            ->where('event_type', 'booking.reservation.created')
            ->first();

        $this->assertNotNull($fact);
        $this->assertSame($traceId, $fact->payload['trace_id'] ?? null);
    }

    public function test_duration_drift_fails_closed_with_conflict(): void
    {
        CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-DRIFT-TEST',
            'name' => 'Drift Test Service',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 90,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $useCase = app(CreateReservationDraftUseCase::class);

        $this->expectException(BookingConflictException::class);

        $useCase->execute(
            tenantId: $this->tenantId,
            userId: $this->userId,
            idempotencyKey: (string) Str::uuid(),
            input: [
                'service_sku' => 'SRV-DRIFT-TEST',
                'scheduled_at' => '2026-12-15 08:30:00',
                'duration_minutes' => 60, // Conflicts with server 90m
            ]
        );
    }
}
