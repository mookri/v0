<?php

namespace Tests\Feature\Calendar;

use App\Domain\Calendar\Profiles\GenerationManager;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

final class CalendarCommandLifecycleTest extends TenantIntegrationTestCase
{
    public function test_full_calendar_command_lifecycle(): void
    {
        $placeId = '01JH0000000000000000000001';

        // 1. Run calendar:build
        $this->artisan('calendar:build', [
            '--year' => 2026,
            '--places' => $placeId,
        ])->assertSuccessful();

        $gen = DB::connection('central')->table('calendar_generations')
            ->where('scope_year', 2026)
            ->first();

        $this->assertNotNull($gen);
        $this->assertSame(GenerationManager::STATE_BUILDING, $gen->state);

        $daysCount = DB::connection('central')->table('calendar_panchang_days')
            ->where('generation', $gen->id)
            ->count();
        $this->assertSame(365, $daysCount);

        // 2. Run calendar:validate
        $this->artisan('calendar:validate', [
            '--generation' => $gen->id,
            '--year' => 2026,
        ])->assertSuccessful();

        $stateValidated = DB::connection('central')->table('calendar_generations')
            ->where('id', $gen->id)
            ->value('state');
        $this->assertSame(GenerationManager::STATE_VALIDATED, $stateValidated);

        // 3. Run calendar:activate
        $this->artisan('calendar:activate', [
            '--generation' => $gen->id,
            '--year' => 2026,
        ])->assertSuccessful();

        $stateActive = DB::connection('central')->table('calendar_generations')
            ->where('id', $gen->id)
            ->value('state');
        $this->assertSame(GenerationManager::STATE_ACTIVE, $stateActive);

        // 4. Run calendar:replay
        $this->artisan('calendar:replay', [
            '--year' => 2026,
            '--date' => '2026-11-10',
        ])->assertSuccessful();
    }
}

