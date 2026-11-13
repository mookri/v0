<?php

namespace App\Modules\Recommendations;

use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->singleton(\App\Modules\Recommendations\Services\ReasonCodeGenerator::class);
        $this->app->singleton(\App\Modules\Recommendations\Services\PopularityCandidateSource::class);
        $this->app->singleton(\App\Modules\Recommendations\Services\ContentTaxonomyCandidateSource::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        \Illuminate\Support\Facades\RateLimiter::for('discovery-public', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->ip());
        });
    }
}

