<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Models\CustomerRitualReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Tests\TenantIntegrationTestCase;

final class ReminderCacheKeyVersionTest extends TenantIntegrationTestCase
{
    public function test_updating_ayanamsa_version_invalidates_cached_occurrence_key(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();
        $userId = 1005;

        config(['calendar.ephemeris_version' => 'meeus-v1']);
        config(['calendar.ayanamsa_version' => 'lahiri-v1']);
        config(['calendar.rules_version' => 2]);

        $rule = [
            'type' => 'CUSTOM_TITHI',
            'index_in_paksha' => 11,
            'rules_version' => 2,
        ];
        $anchorHash = md5(json_encode($rule));
        $now = CarbonImmutable::now('UTC')->toDateString();

        $reminder = CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'CUSTOM_TITHI',
            'title' => 'Cache Key Test Ekadashi',
            'anchor_rule' => $rule,
            'advance_days' => [7, 3, 1],
            'is_active' => true,
        ]);

        $this->clearContext();

        // 1. Run with ayanamsa version v1
        $this->artisan('customers:process-reminders', ['--tenant' => $tenantId])->assertSuccessful();

        $expectedKeyV1 = "panchang:{$tenantId}:{$now}:meeus-v1:lahiri-v1:2:{$anchorHash}";
        $this->assertTrue(Cache::has($expectedKeyV1), 'Cache should contain entry keyed by ayanamsa v1');

        // 2. Bump ayanamsa version to v2
        config(['calendar.ayanamsa_version' => 'lahiri-v2']);

        $expectedKeyV2 = "panchang:{$tenantId}:{$now}:meeus-v1:lahiri-v2:2:{$anchorHash}";
        $this->assertFalse(Cache::has($expectedKeyV2), 'New cache key should not exist prior to recomputation');

        // 3. Re-run daemon: computes into new key, bypassing stale v1 cache
        $this->artisan('customers:process-reminders', ['--tenant' => $tenantId])->assertSuccessful();
        $this->assertTrue(Cache::has($expectedKeyV2), 'Cache should populate entry under ayanamsa v2');
    }
}

