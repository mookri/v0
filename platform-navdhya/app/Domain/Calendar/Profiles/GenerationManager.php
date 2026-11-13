<?php

namespace App\Domain\Calendar\Profiles;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Generation lifecycle and publication manager for astronomical datasets.
 *
 * Enforces atomic pointer swap between computed years/generations:
 * building -> validated -> active -> retired.
 */
final class GenerationManager
{
    public const STATE_BUILDING = 'building';
    public const STATE_VALIDATED = 'validated';
    public const STATE_ACTIVE = 'active';
    public const STATE_RETIRED = 'retired';

    public function activeGeneration(string $profileCode, int $year): ?int
    {
        $row = DB::connection('central')->table('calendar_generations')
            ->where('profile_code', $profileCode)
            ->where('scope_year', $year)
            ->where('state', self::STATE_ACTIVE)
            ->orderByDesc('id')
            ->first();

        return $row !== null ? (int) $row->id : null;
    }

    public function createGeneration(string $profileCode, int $year, array $metrics = []): int
    {
        return (int) DB::connection('central')->table('calendar_generations')->insertGetId([
            'profile_code' => $profileCode,
            'scope_year' => $year,
            'state' => self::STATE_BUILDING,
            'metrics_json' => json_encode($metrics),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markValidated(int $generationId): void
    {
        DB::connection('central')->table('calendar_generations')
            ->where('id', $generationId)
            ->update([
                'state' => self::STATE_VALIDATED,
                'updated_at' => now(),
            ]);
    }

    public function activate(int $generationId): void
    {
        $gen = DB::connection('central')->table('calendar_generations')->where('id', $generationId)->first();
        if ($gen === null) {
            throw new InvalidArgumentException("Generation {$generationId} not found");
        }

        DB::connection('central')->transaction(function () use ($gen, $generationId): void {
            // Retire currently active generation for this profile and year
            DB::connection('central')->table('calendar_generations')
                ->where('profile_code', $gen->profile_code)
                ->where('scope_year', $gen->scope_year)
                ->where('state', self::STATE_ACTIVE)
                ->update([
                    'state' => self::STATE_RETIRED,
                    'updated_at' => now(),
                ]);

            // Promote target generation to active
            DB::connection('central')->table('calendar_generations')
                ->where('id', $generationId)
                ->update([
                    'state' => self::STATE_ACTIVE,
                    'updated_at' => now(),
                ]);
        });
    }

    public function retire(int $generationId): void
    {
        DB::connection('central')->table('calendar_generations')
            ->where('id', $generationId)
            ->update([
                'state' => self::STATE_RETIRED,
                'updated_at' => now(),
            ]);
    }
}

