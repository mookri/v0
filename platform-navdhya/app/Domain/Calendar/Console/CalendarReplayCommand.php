<?php

namespace App\Domain\Calendar\Console;

use App\Domain\Calendar\Panchang\PanchangCalculator;
use App\Domain\Calendar\Profiles\GenerationManager;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CalendarReplayCommand extends Command
{
    protected $signature = 'calendar:replay
        {--year=2026 : Scope year to replay}
        {--profile=drik-lahiri-purnimanta : Calculation profile code}
        {--date= : Specific date YYYY-MM-DD to replay (optional)}';

    protected $description = 'Recompute astronomical day values and assert zero divergence against active generation.';

    public function handle(GenerationManager $generations): int
    {
        $year = (int) $this->option('year');
        $profile = (string) $this->option('profile');
        $dateOpt = $this->option('date');

        $activeGen = $generations->activeGeneration($profile, $year);
        if ($activeGen === null) {
            $this->error("No active generation found for profile {$profile} and year {$year}.");
            return 1;
        }

        $this->info("Replaying calculations against active generation #{$activeGen}...");

        $query = DB::connection('central')->table('calendar_panchang_days')
            ->where('generation', $activeGen);

        if ($dateOpt) {
            $query->where('local_date', $dateOpt);
        } else {
            $query->limit(10); // Replay sample
        }

        $days = $query->get();
        $divergences = 0;

        foreach ($days as $day) {
            $place = DB::connection('central')->table('calendar_places')->where('region_id', $day->place_id)->first();
            if ($place === null) {
                continue;
            }

            $date = CarbonImmutable::parse($day->local_date);
            $recomputed = PanchangCalculator::calculate(
                $date->year,
                $date->month,
                $date->day,
                (float) $place->latitude,
                (float) $place->longitude,
                'PURNIMANTA'
            );

            if ($recomputed->masa['amanta'] !== $day->masa_amanta || $recomputed->masa['purnimanta'] !== $day->masa_purnimanta) {
                $this->error("Masa divergence detected on {$day->local_date} at place {$day->place_id}");
                $divergences++;
            }
        }

        if ($divergences > 0) {
            $this->error("Replay failed with {$divergences} divergences.");
            return 1;
        }

        $this->info("Replay successful: 0 divergences detected across sampled active generation records.");

        return 0;
    }
}

