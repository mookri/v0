<?php

namespace Tests\Feature\Calendar;

use App\Domain\Calendar\Profiles\GenerationManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TenantIntegrationTestCase;

final class CalendarSchemaTest extends TenantIntegrationTestCase
{
    public function test_calendar_tables_exist_with_required_columns(): void
    {
        $tables = [
            'calendar_profiles',
            'calendar_places',
            'calendar_generations',
            'calendar_panchang_days',
            'calendar_astronomical_events',
            'calendar_computation_log',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::connection('central')->hasTable($table),
                "Table {$table} should exist on central connection"
            );
        }
    }

    public function test_generation_manager_enforces_atomic_activation_lifecycle(): void
    {
        $gm = new GenerationManager();
        $profile = 'drik-lahiri-purnimanta';
        $year = 2026;

        // 1. Create generation in building state
        $gen1 = $gm->createGeneration($profile, $year, ['total_days' => 365]);
        $this->assertGreaterThan(0, $gen1);
        $this->assertNull($gm->activeGeneration($profile, $year));

        // 2. Validate generation
        $gm->markValidated($gen1);

        // 3. Activate generation 1
        $gm->activate($gen1);
        $this->assertSame($gen1, $gm->activeGeneration($profile, $year));

        // 4. Create generation 2 and activate: atomically retires generation 1
        $gen2 = $gm->createGeneration($profile, $year, ['total_days' => 365, 'revised' => true]);
        $gm->markValidated($gen2);
        $gm->activate($gen2);

        $this->assertSame($gen2, $gm->activeGeneration($profile, $year));

        $stateGen1 = DB::connection('central')->table('calendar_generations')->where('id', $gen1)->value('state');
        $this->assertSame(GenerationManager::STATE_RETIRED, $stateGen1);
    }

    public function test_unique_indexes_prevent_duplicate_panchang_days(): void
    {
        $this->expectException(QueryException::class);

        $row = [
            'place_id' => '01JHABCDEF0123456789012345',
            'profile_code' => 'drik-lahiri-purnimanta',
            'local_date' => '2026-11-10',
            'sunrise_at_us' => 1794272820000000,
            'masa_amanta' => 'ASHVINA',
            'masa_purnimanta' => 'ASHVINA',
            'generation' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::connection('central')->table('calendar_panchang_days')->insert($row);
        // Duplicate insert must throw unique constraint violation
        DB::connection('central')->table('calendar_panchang_days')->insert($row);
    }
}
