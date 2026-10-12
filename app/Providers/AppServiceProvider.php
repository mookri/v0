<?php

namespace App\Providers;

use App\Platform\Authz\Http\Middleware\CheckCapability;
use App\Platform\Authz\Http\Middleware\CheckScope;
use App\Platform\Authz\Http\Middleware\RequiresAcr;
use App\Platform\Authz\Http\Middleware\ThrottleTier;
use App\Platform\Identity\Http\Middleware\AuthenticateAny;
use App\Platform\Identity\Http\Middleware\AuthenticateHmacToken;
use App\Platform\Identity\Otp\EmailOtpDeliveryProvider;
use App\Platform\Identity\Otp\FirebasePhoneVerificationProvider;
use App\Platform\Identity\Otp\MailerEmailOtpDelivery;
use App\Platform\Identity\Otp\NullEmailOtpDelivery;
use App\Platform\Identity\Otp\OtpAudienceRouter;
use App\Platform\Identity\Otp\OtpProviderRegistry;
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

        // Verification Fabric S47: name -> provider registry built from
        // config/otp-providers.php. Each audience that selects 'firebase'
        // with a complete credential set registers firebase-<audience>;
        // an audience selecting 'firebase' without an API key registers
        // nothing, so dispatch fails closed (typed unavailable error)
        // instead of ever downgrading to a channel that cannot deliver.
        // 'local' is never registered here — services special-case it.
        $this->app->singleton(OtpProviderRegistry::class, static function (): OtpProviderRegistry {
            $providers = [];
            foreach ((array) config('otp-providers.audiences', []) as $audience => $config) {
                $config = (array) $config;
                if (($config['provider'] ?? 'local') !== 'firebase') {
                    continue;
                }

                $firebase = (array) ($config['firebase'] ?? []);
                $apiKey = $firebase['api_key'] ?? null;
                if (! is_string($apiKey) || $apiKey === '' || ! is_string($firebase['project'] ?? null)) {
                    continue; // fail closed: incomplete creds register no provider
                }

                $name = OtpAudienceRouter::nameFor((string) $audience, 'firebase');
                $providers[$name] = new FirebasePhoneVerificationProvider(
                    name: $name,
                    apiKey: $apiKey,
                    timeoutSeconds: (int) ($firebase['timeout_seconds'] ?? 5),
                );
            }

            return new OtpProviderRegistry($providers);
        });

        // Email OTP delivery channel (S48): config-selected between the
        // S46 silent default and the mailer-backed provider. Default
        // 'null' delivers nothing — behavior-identical until an
        // environment opts in with OTP_EMAIL_DELIVERY=mailer.
        $this->app->singleton(EmailOtpDeliveryProvider::class, static function (): EmailOtpDeliveryProvider {
            return config('otp-providers.email_delivery') === MailerEmailOtpDelivery::NAME
                ? new MailerEmailOtpDelivery()
                : new NullEmailOtpDelivery();
        });

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
