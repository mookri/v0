<?php

namespace App\Modules\Pandit;

use App\Modules\Pandit\Application\PanditSchedulePolicyService;
use App\Modules\Pandit\Console\ProcessAssignmentSlaCommand;
use App\Modules\Pandit\Console\RecomputePanditRatingsCommand;
use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Modules\Pandit\Contracts\ManagesPanditAvailability;
use App\Modules\Pandit\Contracts\PanditSchedulePolicy;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Pandit module provider (doc 01 §2-4): port bindings + module migrations.
 * Routes are mounted file-based by the registry (routes/api.php) with the
 * fail-closed auth.any + tenant group — never loaded here, or the bare
 * duplicates would win the match and skip the group middleware.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(ListsPanditProfiles::class, PanditProfileQueries::class);
        $this->app->bind(ManagesPanditAvailability::class, PanditAvailabilityManager::class);
        $this->app->bind(ManagesPanditAssignments::class, PanditAssignmentManager::class);
        $this->app->bind(PanditSchedulePolicy::class, PanditSchedulePolicyService::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessAssignmentSlaCommand::class,
                RecomputePanditRatingsCommand::class,
            ]);
        }
    }
}
