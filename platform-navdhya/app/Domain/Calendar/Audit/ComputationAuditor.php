<?php

namespace App\Domain\Calendar\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Computation auditor for astronomical and Jyotish calculations (T-JYOPT-00 / JY-OPT-00).
 *
 * Persists production telemetry to calendar_computation_log.
 * Fail-safe: telemetry recording errors never abort calculation pipelines.
 */
final class ComputationAuditor
{
    public static function log(
        string $inputHash,
        string $profileCode,
        float $executionMs,
        bool $cacheHit = false,
        int $ephemerisCalls = 1,
        int $rootIterations = 0,
        int $rulesetEvals = 1,
        int $dbQueries = 0,
        int $peakMemBytes = 0
    ): void {
        try {
            DB::connection('central')->table('calendar_computation_log')->insert([
                'input_hash' => substr($inputHash, 0, 64),
                'profile_code' => substr($profileCode, 0, 64),
                'execution_ms' => round($executionMs, 2),
                'cache_hit' => $cacheHit,
                'ephemeris_calls' => $ephemerisCalls,
                'root_iterations' => $rootIterations,
                'ruleset_evals' => $rulesetEvals,
                'db_queries' => $dbQueries,
                'peak_mem_bytes' => $peakMemBytes > 0 ? $peakMemBytes : memory_get_peak_usage(true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::debug("ComputationAuditor::log skipped: " . $e->getMessage());
        }
    }
}

