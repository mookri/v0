<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Domain\Calendar\Panchang\AnchorOccurrenceResolver;
use App\Modules\Customers\Models\CustomerRitualReminder;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

final class StoredAnchorMigrationTest extends TenantIntegrationTestCase
{
    public function test_legacy_reminders_receive_rules_version_1(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $tenantId = (string) $tenant->getKey();
        $userId = (int) $user->id;

        // Legacy reminder created without rules_version
        $reminder = CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'CUSTOM_TITHI',
            'title' => 'Legacy Ekadashi',
            'anchor_rule' => [
                'type' => 'CUSTOM_TITHI',
                'index_in_paksha' => 11,
            ],
            'advance_days' => [7, 3, 1],
            'is_active' => true,
        ]);

        $this->assertArrayNotHasKey('rules_version', $reminder->anchor_rule);

        // Run migration logic (as executed by 2026_11_11_000010_mark_legacy_ritual_reminders_rules_v1)
        CustomerRitualReminder::query()->each(function (CustomerRitualReminder $r): void {
            $rule = $r->anchor_rule ?? [];
            if (! isset($rule['rules_version'])) {
                $rule['rules_version'] = 1;
                $r->updateQuietly(['anchor_rule' => $rule]);
            }
        });

        $refreshed = $reminder->fresh();
        $this->assertNotNull($refreshed);
        $this->assertSame(1, $refreshed->anchor_rule['rules_version']);
    }

    public function test_newly_stored_reminders_default_to_rules_version_2(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->clearContext();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson('/api/v1/customers/reminders', [
                'type' => 'CUSTOM_TITHI',
                'title' => 'Modern Ekadashi V2',
                'anchor_rule' => [
                    'type' => 'CUSTOM_TITHI',
                    'index_in_paksha' => 11,
                    'tradition' => 'PURNIMANTA',
                ],
                'advance_days' => [7, 3, 1],
                'is_active' => true,
            ]);

        $response->assertStatus(201);

        $this->resolveContext($tenant);
        $reminder = CustomerRitualReminder::query()->where('title', 'Modern Ekadashi V2')->first();
        $this->assertNotNull($reminder);
        $this->assertSame(2, $reminder->anchor_rule['rules_version']);
    }

    public function test_version_2_anchor_resolves_via_rectified_sidereal_engine(): void
    {
        $resolver = new AnchorOccurrenceResolver();
        $baseline = CarbonImmutable::parse('2026-11-01 00:00:00', 'Asia/Kolkata');

        $v2Rule = [
            'type' => 'CUSTOM_TITHI',
            'index_in_paksha' => 11,
            'rules_version' => 2,
        ];

        $occurrence = $resolver->occurrenceForAnchor($v2Rule, $baseline, 'Asia/Kolkata', 'PURNIMANTA');
        $this->assertSame(11, $occurrence->tithi['index_in_paksha']);
        $this->assertGreaterThan('2026-11-01', $occurrence->date);
    }
}
