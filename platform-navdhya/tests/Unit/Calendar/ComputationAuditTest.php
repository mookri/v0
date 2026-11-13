<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Profiles\CalculationProfile;
use App\Domain\Calendar\Support\ComputationAuditor;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

final class ComputationAuditTest extends TenantIntegrationTestCase
{
    public function test_computation_auditor_logs_lineage_to_central_table(): void
    {
        $profile = CalculationProfile::defaultProfile();
        $input = [
            'year' => 2026,
            'month' => 11,
            'day' => 10,
            'place_id' => '01JH0000000000000000000001',
        ];

        $hash = ComputationAuditor::log(
            input: $input,
            profile: $profile,
            executionMs: 4.25,
            cacheHit: false,
            result: ['tithi' => 1, 'nakshatra' => 16]
        );

        $this->assertNotEmpty($hash);

        $row = DB::connection('central')->table('calendar_computation_log')
            ->where('input_hash', substr($hash, 0, 64))
            ->first();

        $this->assertNotNull($row);
        $this->assertSame($profile->profile_code, $row->profile_code);
        $this->assertEqualsWithDelta(4.25, (float) $row->execution_ms, 0.01);
        $this->assertFalse((bool) $row->cache_hit);
    }
}

