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
use App\Platform\Identity\OAuth\CustodyAccessToken;
use App\Platform\Identity\OAuth\RotatingRefreshTokenRepository;
use App\Platform\Identity\OAuth\Signing\KmsHttpSigner;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use App\Platform\Identity\OAuth\Signing\SignsTokenRemotely;
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
        $this->app->bind(
            \Laravel\Passport\Bridge\AccessTokenRepository::class,
            \App\Platform\Identity\OAuth\SessionBoundAccessTokenRepository::class
        );

        // Middleware bindings (I4: replace aliases with concrete classes)
        $this->app->bind('auth.any', AuthenticateAny::class);
        $this->app->bind('auth.hmac', AuthenticateHmacToken::class);
        $this->app->bind('tenant', ResolveTenantContext::class);
        $this->app->bind('capability', CheckCapability::class);
        $this->app->bind('scope', CheckScope::class);
        $this->app->bind('throttle.tier', ThrottleTier::class);
        $this->app->bind('stepup', RequiresAcr::class);
        $this->app->bind('authz.current', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class);
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

        // AUTH_MODE (ADR-0020): fail closed at boot, same posture as the
        // OTP provider above — a typo'd mode (or a federated deployment
        // missing its domain/issuer) must refuse to boot instead of
        // serving a surface apps cannot route to.
        \App\Platform\Identity\Federation\FederationMode::current();

        // D3 custody: bind the remote signer port BEFORE the fail-closed
        // custody validation below consults it — under
        // AUTH_SIGNING_CUSTODY=kms every token mint routes through this
        // port (KmsHttpSigner -> the KMS shim's sign endpoint); an
        // unbound port means the custody cannot mint and the boot is
        // refused right below.
        if ((string) config('federation.signing.custody', 'file') === 'kms') {
            $this->app->singleton(
                SignsTokenRemotely::class,
                static fn (): SignsTokenRemotely => new KmsHttpSigner(
                    endpoint: (string) config('federation.signing.kms.endpoint', ''),
                    authToken: (string) config('federation.signing.kms.auth_token', ''),
                    timeoutSeconds: (int) config('federation.signing.kms.timeout_seconds', 3),
                ),
            );
        }

        // AUTH_RUNTIME (ADR-0020 D6): fail closed at boot — an unknown
        // role, an issuer runtime beside a direct mode, or an issuer
        // without signing-key custody refuses to boot (a unified login
        // that can never mint tokens is a broken deployment). Surface
        // separation itself is enforced per request by
        // FederationHostGate + LegacyLoginSurfaceGate.
        \App\Platform\Identity\Federation\RuntimeRole::current();

        // D3 custody mint-path wiring: the access-token entity is
        // custody-aware (league's stock local mint for file custody —
        // byte-exact; the port-backed signer under remote custody), and
        // Passport's own key slots are materialized from the custody
        // provider so league's verification side and JWKS resolve the
        // same single source. Under kms the private slot receives the
        // public PEM as a structural sentinel — anything that slips
        // past the mint interception fails loudly, never signs under a
        // key consumers don't have.
        Passport::useAccessTokenEntity(CustodyAccessToken::class);
        SigningCustody::materializePassportConfig();

        $this->app->singleton(Msg91Client::class, static function (): Msg91Client {
            return new Msg91Client(
                authKey: (string) config('services.msg91.auth_key', ''),
                baseUrl: (string) config('services.msg91.verify_base_url', 'https://control.msg91.com'),
                timeoutSeconds: (int) config('services.msg91.timeout', 5),
                // Deterministic drill/e2e tokens are a local/test affordance
                // only. Production (and any env that is neither) is
                // fail-closed: the shortcut is never honoured, so a leaked
                // magic token cannot authenticate as +91 9898989898.
                allowDevShortcuts: app()->environment(['local', 'testing']),
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
                \App\Platform\Identity\OAuth\Console\RegisterClientKeyCommand::class,
                \App\Platform\Identity\OAuth\Console\RevokeClientKeyCommand::class,
                \App\Platform\Identity\OAuth\Console\RotateSigningKeyCommand::class,
                \App\Platform\Identity\OAuth\Console\RetireSigningKeyCommand::class,
                \App\Platform\Identity\OAuth\Console\RetryBackchannelLogoutCommand::class,
                ListDeviceCodesCommand::class,
                RevokeDeviceCodeCommand::class,
                \App\Platform\Identity\Federation\Console\VerifyAuthEventChainCommand::class,
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
        // The access TTL comes from federation.access_token_ttl_seconds
        // (AUTH_ACCESS_TOKEN_TTL, clamped to SEC-003's 900 s ceiling in
        // config) so the probe's advertised value IS the minted value —
        // deployments may only shorten it (e.g. 90 s), never lengthen
        // it.
        Passport::tokensExpireIn(now()->addSeconds((int) config('federation.access_token_ttl_seconds', 900)));
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
        // D4 (ADR-0020): the same route also records successful refresh
        // exchanges for lost-response recovery (idempotent retry).
        $tokenRoute = Route::getRoutes()->getByName('passport.token');
        if ($tokenRoute !== null) {
            $tokenRoute->middleware(CheckOAuthGrant::class);
            $tokenRoute->middleware(\App\Platform\Identity\OAuth\Http\Middleware\CaptureRefreshResponse::class);
        }

        // 9a (ADR-0021): RFC 9126 PAR enforcement + RFC 9207 `iss` on
        // the authorize routes. Order matters: AppendsIssuer (outer)
        // also catches error redirects minted deeper in the chain
        // (league's own validation errors, PAR denial redirects);
        // RequiresPushedAuthorizationRequest (inner) rewrites the
        // query bag before league validates. Attached by named-route
        // lookup for the same upgrade-safety reason as passport.token.
        $authorizeRoute = Route::getRoutes()->getByName('passport.authorizations.authorize');
        if ($authorizeRoute !== null) {
            $authorizeRoute->middleware(\App\Platform\Identity\OAuth\Http\Middleware\AppendsIssuerToAuthorizationResponse::class);
            $authorizeRoute->middleware(\App\Platform\Identity\OAuth\Http\Middleware\RequiresPushedAuthorizationRequest::class);
            // A-SEC-004-7: prompt validation runs BEFORE any
            // prompt/max_age semantics — a malformed prompt is a
            // rejection, never a re-auth trigger (same doctrine as
            // malformed max_age below).
            $authorizeRoute->middleware(\App\Platform\Identity\OAuth\Http\Middleware\EnforcesPrompt::class);
            // A-SEC-004-5: max_age runs INNERMOST — after the PAR bag
            // rewrite (it reads the authenticated request parameters)
            // and inside AppendsIssuer (its error redirects get `iss`
            // bound on the way out, like every league error).
            $authorizeRoute->middleware(\App\Platform\Identity\OAuth\Http\Middleware\EnforcesMaxAge::class);
        }

        foreach (['passport.authorizations.approve', 'passport.authorizations.deny'] as $authorizeStep) {
            $stepRoute = Route::getRoutes()->getByName($authorizeStep);
            if ($stepRoute !== null) {
                $stepRoute->middleware(\App\Platform\Identity\OAuth\Http\Middleware\AppendsIssuerToAuthorizationResponse::class);
            }
        }

        // 9a (ADR-0021): the `openid` scope — registered so league's
        // scope validation accepts it at the authorize/token seam
        // (Passport::hasScope fails-closed for unregistered scopes).
        // BFF profiles gate who may request it (allowed_scopes).
        Passport::tokensCan(['openid' => 'OpenID Connect authentication.']);

        // D4 (ADR-0020): idempotent refresh recovery + atomic rotation.
        // league's AuthorizationServer keys grants by identifier, so
        // enabling ours after Passport's registration replaces the
        // built-in refresh grant in place — enableGrantType re-applies
        // the server's own keys, emitter and revoke-after-use flag to
        // our subclass, keeping discovery/profiles untouched.
        $this->app->afterResolving(
            \League\OAuth2\Server\AuthorizationServer::class,
            function (\League\OAuth2\Server\AuthorizationServer $server): void {
                // D4: recoverable atomic refresh rotation.
                $grant = new \App\Platform\Identity\OAuth\RecoverableRefreshTokenGrant(
                    $this->app->make(RefreshTokenRepository::class),
                    $this->app->make(\App\Platform\Identity\OAuth\RefreshIdempotencyStore::class),
                );
                $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
                $server->enableGrantType($grant, Passport::tokensExpireIn());

                // D3 (ADR-0020): RFC 7523 private_key_jwt on the other
                // client-authenticated grants. league consults the
                // client repository only for confidential clients
                // holding a non-empty secret — an assertion-only
                // request is rejected as a missing client_secret long
                // before any repository runs — so the assertion branch
                // lives in the grants themselves (one shared trait).
                // 9a (ADR-0021): the enabled grant is OidcAuthCodeGrant —
                // the assertion-capable grant ONE LEVEL DOWN, adding the
                // OIDC deltas (mandatory openid nonce, ceremony facts in
                // the encrypted code payload, ID token from openid code
                // exchanges only). Construction mirrors Passport's
                // buildAuthCodeGrant / client-credentials enablement
                // exactly; keep in sync.
                $authCode = new \App\Platform\Identity\OAuth\OidcAuthCodeGrant(
                    $this->app->make(\Laravel\Passport\Bridge\AuthCodeRepository::class),
                    $this->app->make(RefreshTokenRepository::class),
                    new \DateInterval('PT10M'),
                );
                $authCode->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
                $server->enableGrantType($authCode, Passport::tokensExpireIn());

                $server->enableGrantType(
                    new \App\Platform\Identity\OAuth\AssertionCapableClientCredentialsGrant(),
                    Passport::clientCredentialsTokensExpireIn() ?? Passport::tokensExpireIn()
                );

                // P6 (ADR-0020 D1/D5): RFC 8693 token exchange — subject
                // (and any actor) tokens must verify against THIS
                // issuer's key, the minted token binds to the
                // authenticated exchange client (league picks aud, never
                // the requester), scopes narrow only, and every
                // allow/deny hash-chains. Per-client reachability stays
                // with CheckOAuthGrant (profile) + league's
                // supportsGrantType (client row).
                $server->enableGrantType(
                    new \App\Platform\Identity\OAuth\TokenExchangeGrant(
                        $this->app->make(\App\Platform\Identity\Federation\AuthEventRecorder::class)
                    ),
                    Passport::tokensExpireIn()
                );
            }
        );

        // Federation audit chain (ADR-0020 D4/D5): refresh-token reuse
        // is a mandatory tamper-evident audit event. Wired here because
        // EventServiceProvider is not registered in this kit (skeleton
        // leftover) — activating it would also flip the skeleton's
        // Registered/email-verification listener.
        \Illuminate\Support\Facades\Event::listen(
            \App\Platform\Identity\OAuth\TokenReuseDetected::class,
            \App\Platform\Identity\Federation\Listeners\RecordTokenReuse::class
        );

        // OTP redemptions are authentication decisions (D5): consumed =
        // allow, rejected = deny. Raw identifiers are pseudonymized by
        // the recorder before persistence.
        \Illuminate\Support\Facades\Event::listen(
            \App\Platform\Identity\Otp\OtpChallengeConsumed::class,
            \App\Platform\Identity\Federation\Listeners\RecordOtpConsumed::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Platform\Identity\Otp\OtpChallengeRejected::class,
            \App\Platform\Identity\Federation\Listeners\RecordOtpRejected::class
        );

        // D5: every token issuance hash-chains a token.issued row (jti
        // reference, client, subject, granted scopes) — issuance
        // reconstructs from the chain without any token material, and
        // the RFC 8693 exchange's token.exchanged row shares the same
        // jti reference for correlation.
        \Illuminate\Support\Facades\Event::listen(
            \Laravel\Passport\Events\AccessTokenCreated::class,
            \App\Platform\Identity\Federation\Listeners\RecordTokenIssued::class
        );
    }
}
