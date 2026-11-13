<?php

namespace App\Modules\SeoContent;

use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\SeoContent\Console\SeoImportCommand::class,
            ]);
        }
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        // Named rate limiters (P-15 fix, closes inline throttle hazard)
        RateLimiter::for('seo-public', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('seo-registry', function (Request $request) {
            return Limit::perMinute(600)->by($request->ip());
        });

        RateLimiter::for('panchang-day', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('panchang-month', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('muhurat-find', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('calendar-ics', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
