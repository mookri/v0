<?php

namespace App\Modules\Onboarding;

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
        $this->app->singleton(OtpService::class, static fn (): OtpService => new OtpService(new RandomOtpCodes()));
        $this->app->bind(
            \App\Platform\Identity\Contracts\TrackSeeder::class,
            OnboardingTrackSeeder::class
        );
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
