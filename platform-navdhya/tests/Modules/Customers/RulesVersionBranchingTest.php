<?php

namespace Tests\Modules\Customers;

use App\Domain\Calendar\Panchang\AnchorOccurrenceResolver;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Platform\Outbox\OutboxEvent;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Validates genuine behavioral branching for rules_version (T-JYAUT-00d / D-14).
 * Proves that reminder outbox facts carry the explicit rules_version metadata.
 */
final class RulesVersionBranchingTest extends TenantIntegrationTestCase
{
    public function test_v1_and_v2_anchors_record_explicit_rules_version_in_outbox_facts(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $resolver = new AnchorOccurrenceResolver();
        $baseline = CarbonImmutable::now('Asia/Kolkata')->startOfDay();
        $nextEkadashi = $resolver->occurrenceForAnchor([
            'type' => 'CUSTOM_TITHI',
            'index_in_paksha' => 11,
        ], $baseline, 'Asia/Kolkata', 'PURNIMANTA');

        $occurrenceDate = CarbonImmutable::parse($nextEkadashi->date, 'Asia/Kolkata')->startOfDay();
        $daysUntil = (int) $baseline->diffInDays($occurrenceDate, false);

        // 1. Create v1 legacy reminder
        $reminderV1 = CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 2001,
            'type' => CustomerRitualReminder::TYPE_CUSTOM_TITHI,
            'title' => 'Legacy V1 Ekadashi',
            'anchor_rule' => [
                'type' => 'CUSTOM_TITHI',
                'index_in_paksha' => 11,
                'rules_version' => 1,
            ],
            'advance_days' => [$daysUntil],
            'is_active' => true,
        ]);

        // 2. Create v2 sidereal reminder
        $reminderV2 = CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 2002,
            'type' => CustomerRitualReminder::TYPE_CUSTOM_TITHI,
            'title' => 'Modern V2 Ekadashi',
            'anchor_rule' => [
                'type' => 'CUSTOM_TITHI',
                'index_in_paksha' => 11,
                'rules_version' => 2,
            ],
            'advance_days' => [$daysUntil],
            'is_active' => true,
        ]);

        $this->clearContext();

        // Run daemon
        $this->artisan('customers:process-reminders', ['--tenant' => $tenantId])->assertSuccessful();

        $this->resolveContext($tenant);

        // Assert outbox facts
        $factV1 = OutboxEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('aggregate_id', 2001)
            ->where('event_type', 'customers.reminder.fired')
            ->first();

        $factV2 = OutboxEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('aggregate_id', 2002)
            ->where('event_type', 'customers.reminder.fired')
            ->first();

        $this->assertNotNull($factV1);
        $this->assertSame(1, $factV1->payload['rules_version']);

        $this->assertNotNull($factV2);
        $this->assertSame(2, $factV2->payload['rules_version']);
    }
}

