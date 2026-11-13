<?php

namespace Tests\Feature\Jyotish\Telemetry;

use App\Domain\Calendar\Audit\ComputationAuditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ComputationAuditorLiveLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function testComputationAuditorPersistsTelemetryRecord(): void
    {
        $hash = str_repeat('a', 64);
        $profile = 'drik-lahiri-purnimanta';

        ComputationAuditor::log(
            inputHash: $hash,
            profileCode: $profile,
            executionMs: 14.25,
            cacheHit: false,
            ephemerisCalls: 1,
            rootIterations: 12,
            rulesetEvals: 2,
            dbQueries: 3,
            peakMemBytes: 1048576
        );

        $row = DB::connection('central')->table('calendar_computation_log')
            ->where('input_hash', $hash)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame($profile, $row->profile_code);
        $this->assertEquals(14.25, (float) $row->execution_ms);
        $this->assertFalse((bool) $row->cache_hit);
        $this->assertSame(1, (int) $row->ephemeris_calls);
        $this->assertSame(12, (int) $row->root_iterations);
        $this->assertSame(2, (int) $row->ruleset_evals);
        $this->assertSame(3, (int) $row->db_queries);
        $this->assertSame(1048576, (int) $row->peak_mem_bytes);
    }
}

