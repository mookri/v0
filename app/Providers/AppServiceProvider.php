<?php

namespace App\Providers;

use App\Platform\Identity\OAuth\RotatingRefreshTokenRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Refresh rotation with reuse detection (SEC-003 §3.2): the
        // league rotates by default; our subclass adds lineage kill +
        // security event on revoked-token reuse.
        $this->app->bind(RefreshTokenRepository::class, RotatingRefreshTokenRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // P2 (TOKEN-001): Passport routes on with the consent screen;
        // token lifetimes per SEC-003 (15 min access max, 30 d refresh
        // lineage). Password + implicit grants stay disabled (banned).
        Passport::tokensExpireIn(now()->addMinutes(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::authorizationView(
            static fn (array $parameters): \Symfony\Component\HttpFoundation\Response => response()->view('oauth.authorize', $parameters)
        );
    }
}
