<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Support\DedupKeyGenerator;
use App\Modules\Customers\Models\NotificationDelivery;
use Illuminate\Database\QueryException;
use Tests\TenantIntegrationTestCase;

/**
 * Tests closing C-10:
 * Proves that dedup_key is strictly NOT NULL, preventing MySQL's silent duplicate-on-NULL hole,
 * and validates the deterministic sha256 composition rule.
 */
final class DedupKeyEnforcementTest extends TenantIntegrationTestCase
{
    public function test_dedup_key_generator_produces_deterministic_sha256_hash(): void
    {
        $tenantId = '01JBCDEF000000000000000001';
        $eventType = 'booking.confirmed';
        $recipient = '+919876543210';
        $subjectEntity = 'bkg_01JBCDEF1234567890';
        $revision = 1;

        $key1 = DedupKeyGenerator::generate($tenantId, $eventType, $recipient, $subjectEntity, $revision);
        $key2 = DedupKeyGenerator::generate($tenantId, $eventType, $recipient, $subjectEntity, $revision);

        $expected = hash('sha256', "{$tenantId}:{$eventType}:{$recipient}:{$subjectEntity}:{$revision}");

        $this->assertSame($expected, $key1);
        $this->assertSame($key1, $key2);
        $this->assertSame(64, strlen($key1));

        // Different revision produces different key
        $keyRev2 = DedupKeyGenerator::generate($tenantId, $eventType, $recipient, $subjectEntity, 2);
        $this->assertNotSame($key1, $keyRev2);
    }

    public function test_inserting_delivery_without_dedup_key_throws_database_exception(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $this->expectException(QueryException::class);

        // Attempting to insert a row with NULL dedup_key must be rejected by NOT NULL constraint
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 1007,
            'kind' => 'booking_confirmed',
            'category' => 'BOOKING',
            'channel' => 'WHATSAPP',
            'status' => NotificationDelivery::STATUS_QUEUED,
            'dedup_key' => null, // NOT NULL violation!
        ]);
    }

    public function test_duplicate_dedup_key_within_same_tenant_is_rejected(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $dedupKey = DedupKeyGenerator::generate($tenantId, 'booking.confirmed', '+919876543210', 'bkg_dup_test_1', 1);

        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 1008,
            'kind' => 'booking_confirmed',
            'category' => 'BOOKING',
            'channel' => 'WHATSAPP',
            'status' => NotificationDelivery::STATUS_QUEUED,
            'dedup_key' => $dedupKey,
        ]);

        $this->expectException(QueryException::class);

        // Attempting duplicate insert with same (tenant_id, dedup_key)
        NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 1008,
            'kind' => 'booking_confirmed',
            'category' => 'BOOKING',
            'channel' => 'WHATSAPP',
            'status' => NotificationDelivery::STATUS_QUEUED,
            'dedup_key' => $dedupKey,
        ]);
    }
}

