<?php

namespace App\Domain\Calendar\Support;

use App\Domain\Calendar\Profiles\CalculationProfile;
use Illuminate\Support\Facades\DB;

/**
 * Audit logger capturing full astronomical computation lineage (§9.4).
 */
final class ComputationAuditor
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $result
     */
    public static function log(
        array $input,
        CalculationProfile $profile,
        float $executionMs,
        bool $cacheHit = false,
        array $result = []
    ): string {
        $inputHash = hash('sha256', json_encode([
            'input' => $input,
            'profile' => $profile->toArray(),
        ]));

        DB::connection('central')->table('calendar_computation_log')->insert([
            'input_hash' => substr($inputHash, 0, 64),
            'profile_code' => $profile->profile_code,
            'execution_ms' => round($executionMs, 2),
            'cache_hit' => $cacheHit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $inputHash;
    }
}

