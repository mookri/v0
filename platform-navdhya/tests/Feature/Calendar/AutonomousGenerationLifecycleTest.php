<?php

namespace Tests\Feature\Calendar;

use App\Domain\Calendar\Profiles\GenerationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AutonomousGenerationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function testAutonomousPanchangGenerationDryRunAndWatchdogs(): void
    {
        $profile = 'drik-lahiri-purnimanta';
        $year = 2027;

        // 1. Test horizon watchdog when no active generation exists
        $exitWatchdog = Artisan::call('calendar:horizon-watchdog', [
            '--profile' => $profile,
        ]);
        $this->assertSame(1, $exitWatchdog, 'Watchdog should alert when no active generation exists');

        // 2. Test TZDB drift detection with standard timezones
        $exitTzdb = Artisan::call('calendar:tzdb-drift-detection', [
            '--dry-run' => true,
        ]);
        $this->assertSame(0, $exitTzdb, 'TZDB drift detection should pass when zero corrupted timezones exist');

        // 3. Create a mock active generation for year 2026 to verify watchdog healthy state
        $genMgr = new GenerationManager();
        $genId = $genMgr->createGeneration($profile, (int) date('Y'));
        
        DB::connection('central')->table('calendar_panchang_days')->insert([
            'place_id' => 'delhi_01',
            'profile_code' => $profile,
            'local_date' => date('Y-m-d', strtotime('+120 days')),
            'sunrise_at_us' => 1700000000000000,
            'masa_amanta' => 'KARTIKA',
            'masa_purnimanta' => 'MARGASHIRSHA',
            'generation' => $genId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $genMgr->markValidated($genId);
        $genMgr->activate($genId);

        $exitWatchdogHealthy = Artisan::call('calendar:horizon-watchdog', [
            '--profile' => $profile,
            '--threshold-days' => 90,
        ]);
        $this->assertSame(0, $exitWatchdogHealthy, 'Watchdog should pass when remaining days > threshold');
    }
}

