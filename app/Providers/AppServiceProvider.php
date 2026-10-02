<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // P1 (TOKEN-001): dependency + tables + keys only. Passport's
        // stock routes stay OFF until P2 configures clients and grants —
        // no half-wired /oauth/* surface (the default authorize view
        // binding is a 500 without P2's consent screens).
        Passport::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
