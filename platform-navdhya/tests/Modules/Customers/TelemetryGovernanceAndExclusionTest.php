<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\UserEvent;
use App\Modules\Customers\Telemetry\WarehouseExportService;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

final class TelemetryGovernanceAndExclusionTest extends TenantIntegrationTestCase
{
    public function test_telemetry_ingestion_validates_event_registry_and_payload_caps(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $this->clearContext();

        // 1. Unknown event rejects with 422
        $resUnknown = $this->postJson('/api/v1/customers/events', [
            'events' => [
                [
                    'event_type' => 'unregistered_hack_event',
                    'properties' => ['foo' => 'bar'],
                ],
            ],
        ], ['X-Tenant-Id' => (string) $tenant->getKey()]);

        $resUnknown->assertStatus(422);
        $resUnknown->assertJsonPath('error.code', 'invalid_event_type');

        // 2. Oversized properties payload (>2048 bytes) rejects with 422
        $bigPayload = ['large_text' => str_repeat('A', 2500)];
        $resOversized = $this->postJson('/api/v1/customers/events', [
            'events' => [
                [
                    'event_type' => 'page_view',
                    'properties' => $bigPayload,
                ],
            ],
        ], ['X-Tenant-Id' => (string) $tenant->getKey()]);

        $resOversized->assertStatus(422);
        $resOversized->assertJsonPath('error.code', 'payload_too_large');
    }

    public function test_telemetry_ingestion_drops_events_when_analytics_consent_is_absent(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $this->clearContext();

        // Devotee has NOT granted ANALYTICS consent
        $resNoConsent = $this->postJson('/api/v1/customers/events', [
            'events' => [
                [
                    'event_type' => 'view_item',
                    'properties' => ['item_type' => 'puja', 'value' => 1000],
                ],
            ],
        ], ['X-Tenant-Id' => (string) $tenant->getKey()]);

        $resNoConsent->assertStatus(202);
        $resNoConsent->assertJson([
            'status' => 'accepted',
            'stored' => 0,
            'dropped' => 1,
            'reason' => 'analytics_consent_absent',
        ]);

        $this->resolveContext($tenant);
        $this->assertSame(0, UserEvent::query()->where('user_id', $user->id)->count());

        // Grant ANALYTICS consent
        ConsentLedger::query()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->id,
            'purpose' => ConsentLedger::PURPOSE_ANALYTICS,
            'state' => ConsentLedger::STATE_GRANTED,
            'source' => 'SETTINGS_PORTAL',
            'policy_version' => '2026.1',
        ]);
        $this->clearContext();

        // Submit again
        $resWithConsent = $this->postJson('/api/v1/customers/events', [
            'events' => [
                [
                    'event_type' => 'view_item',
                    'properties' => ['item_type' => 'puja', 'value' => 1000],
                ],
            ],
        ], ['X-Tenant-Id' => (string) $tenant->getKey()]);

        $resWithConsent->assertStatus(202);
        $resWithConsent->assertJson([
            'status' => 'accepted',
            'stored' => 1,
            'dropped' => 0,
        ]);

        $this->resolveContext($tenant);
        $this->assertSame(1, UserEvent::query()->where('user_id', $user->id)->count());
    }

    public function test_purge_telemetry_command_cleanses_old_records(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        // 1. Old event (120 days ago)
        UserEvent::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 1001,
            'event_type' => 'page_view',
            'properties' => ['page_type' => 'home'],
            'source' => UserEvent::SOURCE_API,
            'purpose' => 'ANALYTICS',
            'occurred_at' => CarbonImmutable::now('UTC')->subDays(120),
        ]);

        // 2. Recent event (10 days ago)
        UserEvent::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 1001,
            'event_type' => 'page_view',
            'properties' => ['page_type' => 'puja_detail'],
            'source' => UserEvent::SOURCE_API,
            'purpose' => 'ANALYTICS',
            'occurred_at' => CarbonImmutable::now('UTC')->subDays(10),
        ]);

        $this->clearContext();

        $this->artisan('customers:purge-telemetry')->assertSuccessful();

        $this->resolveContext($tenant);
        $this->assertSame(1, UserEvent::query()->count());
    }

    public function test_warehouse_export_row_walker_strictly_excludes_spiritual_data(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();
        $userId = 10088;

        // Create an event that maliciously or accidentally includes sensitive spiritual keys
        UserEvent::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event_type' => 'booking_confirmed',
            'properties' => [
                'value' => 150000,
                'currency' => 'INR',
                'service_category' => 'homa',
                'city' => 'Bengaluru',
                // Special-category spiritual data that MUST be excluded
                'gotra' => 'Kashyapa',
                'nakshatra' => 'Rohini',
                'rashi' => 'Vrishabha',
                'kuladevata_name' => 'Sri Venkateshwara',
                'shraddha_date' => '2026-10-11',
            ],
            'context' => [
                'route_template' => '/book/confirm',
            ],
            'source' => UserEvent::SOURCE_API,
            'purpose' => 'ANALYTICS',
            'occurred_at' => now(),
        ]);

        $exportService = new WarehouseExportService();
        $batch = $exportService->exportBatch('test_warehouse_sync');

        $this->assertCount(1, $batch['envelopes']);
        $envelope = $batch['envelopes'][0];

        // 1. Verify standard SignalEnvelope structure
        $this->assertSame(WarehouseExportService::RESERVED_SOURCE, $envelope['source']);
        $this->assertNotEmpty($envelope['actor']['uid_hash']);
        $this->assertNotSame((string) $userId, $envelope['actor']['uid_hash']); // Must be HMAC hashed
        $this->assertSame('/book/confirm', $envelope['route_template']);

        // 2. Strict Row-Walking Exclusion Assertion
        $bannedTerms = ['gotra', 'nakshatra', 'rashi', 'kuladevata', 'shraddha'];
        $serializedEnvelope = json_encode($envelope);
        $this->assertIsString($serializedEnvelope);

        foreach ($bannedTerms as $term) {
            $this->assertStringNotContainsString(
                $term,
                strtolower($serializedEnvelope),
                "Privacy violation: special-category field '{$term}' leaked into warehouse export envelope!"
            );
        }

        // Allowed primitive parameters are present
        $this->assertSame(150000, $envelope['params']['value']);
        $this->assertSame('INR', $envelope['params']['currency']);
        $this->assertSame('homa', $envelope['params']['service_category']);
    }
}
