<?php

namespace App\Providers;

use App\Platform\Authz\Http\Middleware\CheckCapability;
use App\Platform\Authz\Http\Middleware\CheckScope;
use App\Platform\Authz\Http\Middleware\RequiresAcr;
use App\Platform\Authz\Http\Middleware\ThrottleTier;
use App\Platform\Identity\Http\Middleware\AuthenticateAny;
use App\Platform\Identity\Http\Middleware\AuthenticateHmacToken;
use App\Platform\Identity\Otp\DbOtpChallengeStore;
use App\Platform\Identity\Otp\Msg91Client;
use App\Platform\Identity\Otp\Msg91WidgetProvider;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpChallengeStore;
use App\Platform\Identity\Otp\OtpProviderConfigurationException;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\VerifiesOtpTokens;
use App\Platform\Identity\OAuth\CheckOAuthGrant;
use App\Platform\Identity\OAuth\Console\CreateClientCommand;
use App\Platform\Identity\OAuth\Console\ListDeviceCodesCommand;
use App\Platform\Identity\OAuth\Console\RevokeClientCommand;
use App\Platform\Identity\OAuth\Console\RevokeDeviceCodeCommand;
use App\Platform\Identity\OAuth\Console\RotateClientSecretCommand;
use App\Platform\Identity\OAuth\RotatingRefreshTokenRepository;
use App\Platform\Tenancy\Http\Middleware\ResolveTenantContext;
use Illuminate\Support\Facades\Route;
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

        // Middleware bindings (I4: replace aliases with concrete classes)
        $this->app->bind('auth.any', AuthenticateAny::class);
        $this->app->bind('auth.hmac', AuthenticateHmacToken::class);
        $this->app->bind('tenant', ResolveTenantContext::class);
        $this->app->bind('capability', CheckCapability::class);
        $this->app->bind('scope', CheckScope::class);
        $this->app->bind('throttle.tier', ThrottleTier::class);
        $this->app->bind('stepup', RequiresAcr::class);
        $this->app->bind('limit.body', \App\Platform\Http\Middleware\LimitJsonBody::class);

        // OTP subsystem (ADR-002): MSG91 widget provider everywhere.
        // Fail closed at boot — MSG91 is the only channel, so a
        // missing server-only auth key is a broken deployment, not a
        // degraded one: refuse to boot rather than serve challenges
        // that can never verify.
        $authKey = (string) config('services.msg91.auth_key', '');
        if ($authKey === '') {
            throw new OtpProviderConfigurationException('services.msg91.auth_key is not configured; the msg91_widget OTP provider cannot verify tokens');
        }

        $this->app->singleton(Msg91Client::class, static function (): Msg91Client {
            return new Msg91Client(
                authKey: (string) config('services.msg91.auth_key', ''),
                baseUrl: (string) config('services.msg91.verify_base_url', 'https://control.msg91.com'),
                timeoutSeconds: (int) config('services.msg91.timeout', 5),
            );
        });

        // Token-verification port -> client. A future provider swap
        // rebinds this interface; nothing else in the subsystem knows
        // the vendor (ADR-002 recipe 1).
        $this->app->bind(VerifiesOtpTokens::class, Msg91Client::class);

        $this->app->singleton(OtpProviderRegistry::class, static function (): OtpProviderRegistry {
            return new OtpProviderRegistry([
                Msg91WidgetProvider::NAME => new Msg91WidgetProvider(app(VerifiesOtpTokens::class)),
            ]);
        });

        // One manager class, two stores: customer auth + MFA step-up
        // ride the central table (default); modules on a different
        // table declare their store with a contextual binding (see
        // Onboarding's registration) — ADR-002 recipe 3.
        $this->app->when(OtpChallengeManager::class)
            ->needs(OtpChallengeStore::class)
            ->give(static fn (): OtpChallengeStore => DbOtpChallengeStore::customer());

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateClientCommand::class,
                RevokeClientCommand::class,
                RotateClientSecretCommand::class,
                ListDeviceCodesCommand::class,
                RevokeDeviceCodeCommand::class,
            ]);
        }
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
        Passport::deviceUserCodeView('oauth.device.user-code');
        Passport::deviceAuthorizationView('oauth.device.authorize');

        // P3a: per-client grant enforcement on the token route. Wired
        // here (not in routes files) because the route is registered by
        // Passport's own provider; the named-route lookup keeps this
        // working across Passport upgrades that preserve route names.
        $tokenRoute = Route::getRoutes()->getByName('passport.token');
        if ($tokenRoute !== null) {
            $tokenRoute->middleware(CheckOAuthGrant::class);
        }
    }
}
