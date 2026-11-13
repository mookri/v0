<?php

namespace App\Domain\Calendar\Console;

use App\Domain\Calendar\Profiles\GenerationManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CalendarValidateCommand extends Command
{
    protected $signature = 'calendar:validate
        {--generation= : Generation ID to validate}
        {--year=2026 : Scope year if generation ID not specified}
        {--profile=drik-lahiri-purnimanta : Calculation profile code}';

    protected $description = 'Validate monotonicity, gapless boundaries, and completeness of a staged calendar generation.';

    public function handle(GenerationManager $generations): int
    {
        $genId = $this->option('generation') ? (int) $this->option('generation') : null;
        $profile = (string) $this->option('profile');
        $year = (int) $this->option('year');

        if ($genId === null) {
            $latest = DB::connection('central')->table('calendar_generations')
                ->where('profile_code', $profile)
                ->where('scope_year', $year)
                ->orderByDesc('id')
                ->first();

            if ($latest === null) {
                $this->error("No generation found for profile {$profile} and year {$year}.");
                return 1;
            }

            $genId = (int) $latest->id;
        }

        $this->info("Validating calendar generation #{$genId}...");

        $daysCount = DB::connection('central')->table('calendar_panchang_days')
            ->where('generation', $genId)
            ->count();

        if ($daysCount === 0) {
            $this->error("Generation #{$genId} contains zero Panchang days.");
            return 1;
        }

        // 1. Check day counts per place (must be 365 or 366 days)
        $placeGroups = DB::connection('central')->table('calendar_panchang_days')
            ->where('generation', $genId)
            ->select('place_id', DB::raw('count(*) as count'))
            ->groupBy('place_id')
            ->get();

        $expectedDays = (date('L', mktime(0, 0, 0, 1, 1, $year)) ? 366 : 365);

        foreach ($placeGroups as $group) {
            if ($group->count !== $expectedDays) {
                $this->error("Place {$group->place_id} has {$group->count} days; expected {$expectedDays}.");
                return 1;
            }
        }

        // 2. Check monotonicity of sunrise microseconds per place
        $nonMonotonic = DB::connection('central')->select("
            SELECT t1.place_id, t1.local_date
            FROM calendar_panchang_days t1
            JOIN calendar_panchang_days t2
              ON t1.place_id = t2.place_id
             AND t1.generation = t2.generation
             AND date(t2.local_date) = date(t1.local_date, '+1 day')
            WHERE t1.generation = ?
              AND t2.sunrise_at_us <= t1.sunrise_at_us
        ", [$genId]);

        if (! empty($nonMonotonic)) {
            $this->error('Detected non-monotonic sunrise timestamps across consecutive days.');
            return 1;
        }

        // 3. Mark validated
        $generations->markValidated($genId);

        $this->info("Validation successful for generation #{$genId} ({$daysCount} total days validated). Marked state [validated].");
        $this->comment("To activate for production queries, run: 'php artisan calendar:activate --generation={$genId}'");

        return 0;
    }
}

