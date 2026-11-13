<?php

namespace Tests\Modules\Catalog;

use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Domain\Exceptions\BookingConflictException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Catalog\Contracts\ResolvesServiceDurations;
use App\Modules\Catalog\Contracts\ResolvesServiceKitBindings;
use App\Modules\Catalog\Domain\Duration\DurationRules;
use App\Modules\Catalog\Models\CatalogDurationRule;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogServiceKitBinding;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class DurationRulesAndKitBindingsTest extends TenantIntegrationTestCase
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

    public function test_pure_domain_duration_rules_engine_evaluates_deterministically(): void
    {
        $baseMinutes = 60;
        $rules = [
            [
                'scope' => 'PER_PARTICIPANT',
                'scope_key' => 'default',
                'rule' => [
                    'per' => 2,
                    'add' => 15,
                ],
            ],
            [
                'scope' => 'PACKAGE_TIER',
                'scope_key' => 'PREMIUM',
                'rule' => [
                    'add' => 30,
                    'min_bound' => 90,
                    'max_bound' => 180,
                ],
            ],
        ];

        // 5 participants: floor((5 - 1) / 2) * 15 = 2 * 15 = 30 min add.
        // Package tier PREMIUM: +30 min.
        // Total expected = 60 + 30 + 30 = 120 min.
        $result = DurationRules::resolve(
            baseMinutes: $baseMinutes,
            rules: $rules,
            participants: 5,
            packageTier: 'PREMIUM'
        );

        $this->assertSame(120, $result->minutes);
        $this->assertSame(90, $result->minBound);
        $this->assertSame(180, $result->maxBound);
        $this->assertNotEmpty($result->ruleSnapshotHash);
        $this->assertCount(2, $result->evaluatedRules);
    }

    public function test_create_reservation_draft_resolves_authoritative_duration_and_snapshots_rules(): void
    {
        $serviceItem = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-SATYANARAYAN',
            'name' => 'Satyanarayan Katha',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 90,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        CatalogDurationRule::create([
            'tenant_id' => $this->tenantId,
            'item_id' => (string) $serviceItem->getKey(),
            'scope' => 'PER_PARTICIPANT',
            'scope_key' => 'default',
            'rule' => ['per' => 1, 'add' => 10],
        ]);

        $useCase = app(CreateReservationDraftUseCase::class);

        // 3 participants: base 90 + (3-1)*10 = 110 minutes
        $booking = $useCase->execute(
            tenantId: $this->tenantId,
            userId: $this->userId,
            idempotencyKey: (string) Str::uuid(),
            input: [
                'service_sku' => 'SRV-SATYANARAYAN',
                'scheduled_at' => '2026-12-01 10:00:00',
                'participants' => 3,
            ]
        );

        $this->assertSame(110, $booking->duration_minutes);

        $revision = $booking->revisions()->first();
        $this->assertNotNull($revision);

        $lines = BookingLine::query()->where('revision_id', (string) $revision->getKey())->get();
        $this->assertCount(1, $lines);

        $serviceLine = $lines->first();
        $this->assertSame('SERVICE', $serviceLine->line_kind);
        $this->assertSame(110, $serviceLine->server_duration_minutes);
        $this->assertNotNull($serviceLine->duration_rule_snapshot);
        $this->assertSame(110, $serviceLine->duration_rule_snapshot['minutes']);
    }

    public function test_duration_drift_is_rejected_with_409_conflict(): void
    {
        CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-GANESH-PUJA',
            'name' => 'Ganesh Puja',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 60,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $useCase = app(CreateReservationDraftUseCase::class);

        try {
            $useCase->execute(
                tenantId: $this->tenantId,
                userId: $this->userId,
                idempotencyKey: (string) Str::uuid(),
                input: [
                    'service_sku' => 'SRV-GANESH-PUJA',
                    'scheduled_at' => '2026-12-01 10:00:00',
                    'duration_minutes' => 45, // Conflicts with server 60m
                ]
            );
            $this->fail('Expected BookingConflictException was not thrown.');
        } catch (BookingConflictException $e) {
            $this->assertSame('duration_drift', $e->errorCode);
            $this->assertStringContainsString('conflicts with authoritative server duration', $e->getMessage());
        }
    }

    public function test_service_kit_bindings_auto_expand_into_booking_lines(): void
    {
        $serviceItem = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-RUDRA-ABHISHEK',
            'name' => 'Rudra Abhishek',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 120,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $reqKit = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'KIT-RUDRA-SAMAGRI',
            'name' => 'Rudra Samagri Kit',
            'item_kind' => CatalogItem::KIND_KIT,
            'base_duration_minutes' => 0,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $incKit = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'KIT-PRASAD-BOX',
            'name' => 'Prasad Offering Box',
            'item_kind' => CatalogItem::KIND_KIT,
            'base_duration_minutes' => 0,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $optKit = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'KIT-SILVER-COIN',
            'name' => 'Silver Coin Token',
            'item_kind' => CatalogItem::KIND_KIT,
            'base_duration_minutes' => 0,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        // Required binding: 1 per party
        CatalogServiceKitBinding::create([
            'tenant_id' => $this->tenantId,
            'service_item_id' => (string) $serviceItem->getKey(),
            'kit_item_id' => (string) $reqKit->getKey(),
            'obligation' => CatalogServiceKitBinding::OBLIGATION_REQUIRED,
            'quantity_per_party' => 1,
            'sort' => 10,
        ]);

        // Included binding: 1 per booking at zero price
        CatalogServiceKitBinding::create([
            'tenant_id' => $this->tenantId,
            'service_item_id' => (string) $serviceItem->getKey(),
            'kit_item_id' => (string) $incKit->getKey(),
            'obligation' => CatalogServiceKitBinding::OBLIGATION_INCLUDED,
            'quantity_per_party' => 1,
            'sort' => 20,
        ]);

        // Optional binding: opt-in only
        CatalogServiceKitBinding::create([
            'tenant_id' => $this->tenantId,
            'service_item_id' => (string) $serviceItem->getKey(),
            'kit_item_id' => (string) $optKit->getKey(),
            'obligation' => CatalogServiceKitBinding::OBLIGATION_OPTIONAL,
            'quantity_per_party' => 1,
            'sort' => 30,
        ]);

        $useCase = app(CreateReservationDraftUseCase::class);

        // Scenario A: Caller does NOT opt into optional kit; 2 participants
        $bookingA = $useCase->execute(
            tenantId: $this->tenantId,
            userId: $this->userId,
            idempotencyKey: (string) Str::uuid(),
            input: [
                'service_sku' => 'SRV-RUDRA-ABHISHEK',
                'scheduled_at' => '2026-12-01 10:00:00',
                'participants' => 2,
            ]
        );

        $revisionA = $bookingA->revisions()->first();
        $linesA = BookingLine::query()->where('revision_id', (string) $revisionA->getKey())->get();

        // Expect 3 lines: 1 service + 1 required kit (qty: 2*1=2) + 1 included kit (qty: 1, price: 0)
        $this->assertCount(3, $linesA);

        $srvLine = $linesA->firstWhere('line_kind', 'SERVICE');
        $this->assertNotNull($srvLine);
        $this->assertSame('SRV-RUDRA-ABHISHEK', $srvLine->sku);

        $reqLine = $linesA->firstWhere('sku', 'KIT-RUDRA-SAMAGRI');
        $this->assertNotNull($reqLine);
        $this->assertSame(2, $reqLine->quantity);
        $this->assertSame((string) $srvLine->getKey(), $reqLine->parent_line_id);

        $incLine = $linesA->firstWhere('sku', 'KIT-PRASAD-BOX');
        $this->assertNotNull($incLine);
        $this->assertSame(1, $incLine->quantity);
        $this->assertSame(0, $incLine->price_cents);
        $this->assertSame((string) $srvLine->getKey(), $incLine->parent_line_id);

        // Optional kit should NOT be present in Scenario A
        $this->assertNull($linesA->firstWhere('sku', 'KIT-SILVER-COIN'));

        // Scenario B: Caller opts in to the optional kit
        $bookingB = $useCase->execute(
            tenantId: $this->tenantId,
            userId: $this->userId,
            idempotencyKey: (string) Str::uuid(),
            input: [
                'service_sku' => 'SRV-RUDRA-ABHISHEK',
                'scheduled_at' => '2026-12-02 10:00:00',
                'participants' => 1,
                'kit_optins' => ['KIT-SILVER-COIN'],
            ]
        );

        $revisionB = $bookingB->revisions()->first();
        $linesB = BookingLine::query()->where('revision_id', (string) $revisionB->getKey())->get();

        // Expect 4 lines: service + required + included + optional
        $this->assertCount(4, $linesB);
        $optLine = $linesB->firstWhere('sku', 'KIT-SILVER-COIN');
        $this->assertNotNull($optLine);
        $this->assertSame(1, $optLine->quantity);
    }

    public function test_catalog_lint_bindings_command_succeeds_for_valid_bindings(): void
    {
        $service = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-LINT-TEST',
            'name' => 'Lint Test Service',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 60,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $kit = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'KIT-LINT-TEST',
            'name' => 'Lint Test Kit',
            'item_kind' => CatalogItem::KIND_KIT,
            'base_duration_minutes' => 0,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        CatalogServiceKitBinding::create([
            'tenant_id' => $this->tenantId,
            'service_item_id' => (string) $service->getKey(),
            'kit_item_id' => (string) $kit->getKey(),
            'obligation' => CatalogServiceKitBinding::OBLIGATION_REQUIRED,
            'quantity_per_party' => 1,
            'sort' => 1,
        ]);

        $this->artisan('catalog:lint-bindings', ['--tenant' => $this->tenantId])
            ->assertExitCode(0);
    }
}
