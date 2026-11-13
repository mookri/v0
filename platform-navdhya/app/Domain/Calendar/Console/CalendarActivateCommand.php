<?php

namespace App\Domain\Calendar\Console;

use App\Domain\Calendar\Profiles\GenerationManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CalendarActivateCommand extends Command
{
    protected $signature = 'calendar:activate
        {--generation= : Generation ID to activate}
        {--year=2026 : Scope year if generation ID not specified}
        {--profile=drik-lahiri-purnimanta : Calculation profile code}';

    protected $description = 'Atomically activate a validated calendar generation as the active serving generation.';

    public function handle(GenerationManager $generations): int
    {
        $genId = $this->option('generation') ? (int) $this->option('generation') : null;
        $profile = (string) $this->option('profile');
        $year = (int) $this->option('year');

        if ($genId === null) {
            $validated = DB::connection('central')->table('calendar_generations')
                ->where('profile_code', $profile)
                ->where('scope_year', $year)
                ->where('state', GenerationManager::STATE_VALIDATED)
                ->orderByDesc('id')
                ->first();

            if ($validated === null) {
                $this->error("No validated generation found for profile {$profile} and year {$year}. Run calendar:validate first.");
                return 1;
            }

            $genId = (int) $validated->id;
        }

        $generations->activate($genId);

        $this->info("Generation #{$genId} is now ACTIVE for profile '{$profile}' and year {$year}. Previous active generation retired atomically.");

        return 0;
    }
}

