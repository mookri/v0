<?php

namespace App\Domain\Calendar\Console;

use App\Domain\Calendar\Profiles\GenerationManager;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generation horizon watchdog (T-JYAUT-03 / JY-AUT-03).
 *
 * Runs hourly; alerts operations if active generation horizon is < 90 days.
 */
final class GenerationHorizonWatchdogCommand extends Command
{
    protected $signature = 'calendar:horizon-watchdog
        {--threshold-days=90 : Minimum safe horizon in days}
        {--profile=drik-lahiri-purnimanta : Calculation profile code}';

    protected $description = 'Monitor active calendar generation horizon and alert operations if < 90 days remain.';

    public function handle(GenerationManager $generations): int
    {
        $profile = (string) $this->option('profile');
        $threshold = (int) $this->option('threshold-days');
        $currentYear = (int) date('Y');

        $activeGenId = $generations->activeGeneration($profile, $currentYear);
        if ($activeGenId === null) {
            $this->error("ALERT: No active calendar generation found for profile '{$profile}' and year {$currentYear}!");
            Log::alert("Calendar horizon watchdog: No active generation", ['profile' => $profile, 'year' => $currentYear]);
            return 1;
        }

        // Query max available date in panchang days
        $maxDateStr = DB::connection('central')->table('calendar_panchang_days')
            ->where('generation', $activeGenId)
            ->max('local_date');

        if ($maxDateStr === null) {
            $this->error("Active generation #{$activeGenId} has no computed Panchang days.");
            return 1;
        }

        $now = CarbonImmutable::now('UTC');
        $maxDate = CarbonImmutable::parse($maxDateStr, 'UTC');
        $daysRemaining = (int) $now->diffInDays($maxDate, false);

        if ($daysRemaining < $threshold) {
            $this->warn("WARNING: Calendar horizon is critically low: {$daysRemaining} days remaining (threshold: {$threshold} days). Max date: {$maxDateStr}.");
            Log::warning("Calendar horizon low", [
                'profile' => $profile,
                'active_generation' => $activeGenId,
                'days_remaining' => $daysRemaining,
                'threshold' => $threshold,
                'max_date' => $maxDateStr,
            ]);
            return 1;
        }

        $this->info("Calendar horizon healthy: {$daysRemaining} days remaining until {$maxDateStr} (active generation #{$activeGenId}).");
        return 0;
    }
}
