<?php

namespace App\Modules\Onboarding;

use App\Modules\Onboarding\Http\Controllers\SubmitController;
use App\Platform\Identity\Otp\DbOtpChallengeStore;
use App\Platform\Identity\Otp\OtpChallengeStore;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Onboarding module provider (doc 01 §2-4): loads the module's migrations;
 * routes mount file-based via the registry (routes/api.php), policies land
 * with the reviewer surface.
 */
final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        // Submit OTP rides the shared OtpChallengeManager; this module's
        // challenges live in the tenant-shared otp_challenges table, so
        // the store seam is bound contextually (every other consumer of
        // the manager keeps the platform default).
        $this->app->when(SubmitController::class)
            ->needs(OtpChallengeStore::class)
            ->give(static fn (): OtpChallengeStore => DbOtpChallengeStore::onboarding());

        $this->app->bind(
            \App\Platform\Identity\Contracts\TrackSeeder::class,
            OnboardingTrackSeeder::class
        );
        $this->app->bind(
            \App\Modules\Onboarding\Contracts\ManagesPartnerApplications::class,
            PartnerApplicationService::class
        );
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
