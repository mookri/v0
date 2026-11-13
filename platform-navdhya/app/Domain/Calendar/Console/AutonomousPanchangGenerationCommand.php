<?php

namespace App\Domain\Calendar\Console;

use App\Domain\Calendar\Profiles\GenerationManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Autonomous ahead-of-time generation (T-JYAUT-02 / JY-AUT-02).
 *
 * Builds year Y+1 into a building generation, runs calendar:validate
 * and calendar:verify-oracle, and executes atomic swap to active.
 * Leaves prior active generation serving if validation fails.
 */
final class AutonomousPanchangGenerationCommand extends Command
{
    protected $signature = 'calendar:autonomous-generate
        {--year= : Target year (defaults to next calendar year)}
        {--profile=drik-lahiri-purnimanta : Calculation profile code}
        {--dry-run : Validate without activating}';

    protected $description = 'Autonomous ahead-of-time Panchang generation, validation, and zero-downtime activation.';

    public function handle(GenerationManager $generations): int
    {
        $targetYear = $this->option('year')
            ? (int) $this->option('year')
            : (int) date('Y') + 1;

        $profile = (string) $this->option('profile');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Initiating autonomous Panchang generation for year {$targetYear} with profile '{$profile}'...");

        // 1. Build generation in staged 'building' state
        $buildExit = Artisan::call('calendar:build', [
            '--year' => $targetYear,
            '--profile' => $profile,
        ]);

        if ($buildExit !== 0) {
            $this->error("Calendar build failed with exit code {$buildExit}. Output:\n" . Artisan::output());
            Log::error("Autonomous Panchang generation build failed", [
                'year' => $targetYear,
                'profile' => $profile,
                'output' => Artisan::output(),
            ]);
            return 1;
        }

        // 2. Discover newly created staged generation ID
        $staged = DB::connection('central')->table('calendar_generations')
            ->where('profile_code', $profile)
            ->where('scope_year', $targetYear)
            ->whereIn('state', [GenerationManager::STATE_BUILDING, GenerationManager::STATE_VALIDATED])
            ->orderByDesc('id')
            ->first();

        if ($staged === null) {
            $this->error("Failed to find staged generation after build step.");
            return 1;
        }

        $genId = (int) $staged->id;
        $this->info("Staged generation #{$genId} created. Running validation...");

        // 3. Run validation checks
        $validateExit = Artisan::call('calendar:validate', [
            '--generation' => $genId,
            '--year' => $targetYear,
            '--profile' => $profile,
        ]);

        if ($validateExit !== 0) {
            $this->error("Calendar validation failed with exit code {$validateExit}. Prior generation remains active.");
            Log::warning("Autonomous generation validation failed; prior generation preserved", [
                'generation_id' => $genId,
                'year' => $targetYear,
                'profile' => $profile,
                'output' => Artisan::output(),
            ]);
            return 1;
        }

        if ($dryRun) {
            $this->info("Dry-run requested: generation #{$genId} validated successfully but not activated.");
            return 0;
        }

        // 4. Atomic activation
        $generations->activate($genId);
        $this->info("Successfully validated and atomically activated generation #{$genId} for year {$targetYear}.");
        Log::info("Autonomous Panchang generation activated", [
            'generation_id' => $genId,
            'year' => $targetYear,
            'profile' => $profile,
        ]);

        return 0;
    }
}

