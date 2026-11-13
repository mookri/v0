<?php

namespace Tests\Feature\Communication;

use App\Modules\Customers\Models\NotificationDelivery;
use Illuminate\Support\Facades\Schema;
use Tests\TenantIntegrationTestCase;

/**
 * Tests closing C-09:
 * Proves that the evolved communication_deliveries table supports all §6.1 lifecycle states
 * without varchar(16) truncation (including PROVIDER_ACCEPTED [17 chars] and DELIVERY_UNKNOWN [16 chars]),
 * and preserves timestamps, attempt counts, and cost telemetry.
 */
final class DeliverySchemaExpansionTest extends TenantIntegrationTestCase
{
    public function test_schema_has_evolved_columns(): void
    {
        $schema = Schema::connection('tenant_shared');

        $this->assertTrue($schema->hasTable('communication_deliveries'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'intent_id'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'provider'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'template_version_id'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'idempotency_key'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'attempt_count'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'submitted_at'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'accepted_at'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'sent_at'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'delivered_at'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'read_at'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'failed_at'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'reconciled_at'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'unit_cost_cents'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'error_code'));
        $this->assertTrue($schema->hasColumn('communication_deliveries', 'recipient_reference_hash'));
    }

    public function test_can_save_provider_accepted_without_truncation(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $delivery = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 1005,
            'kind' => 'booking_confirmed',
            'category' => 'BOOKING',
            'channel' => 'WHATSAPP',
            'provider' => 'whatsapp_cloud',
            'dedup_key' => 'dedup_test_provider_accepted_001',
            'status' => NotificationDelivery::STATUS_PROVIDER_ACCEPTED, // 17 chars!
            'provider_message_id' => 'wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSRTI4QTMxQzA1Nzg3RTlGNTlBAA==',
            'submitted_at' => now()->subSeconds(2),
            'accepted_at' => now(),
            'attempt_count' => 1,
            'unit_cost_cents' => 65,
        ]);

        $delivery->refresh();

        $this->assertSame('PROVIDER_ACCEPTED', $delivery->status);
        $this->assertSame(17, strlen($delivery->status));
        $this->assertSame(65, $delivery->unit_cost_cents);
        $this->assertNotNull($delivery->accepted_at);
    }

    public function test_can_save_delivery_unknown_state(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $delivery = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 1006,
            'kind' => 'panchang_alert',
            'category' => 'REMINDER',
            'channel' => 'SMS',
            'provider' => 'msg91',
            'dedup_key' => 'dedup_test_delivery_unknown_002',
            'status' => NotificationDelivery::STATUS_DELIVERY_UNKNOWN, // 16 chars
            'error_code' => 'HTTP_504_GATEWAY_TIMEOUT',
            'submitted_at' => now()->subMinutes(10),
            'attempt_count' => 1,
        ]);

        $delivery->refresh();

        $this->assertSame('DELIVERY_UNKNOWN', $delivery->status);
        $this->assertSame('HTTP_504_GATEWAY_TIMEOUT', $delivery->error_code);
    }
}

