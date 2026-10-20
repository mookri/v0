<?php

namespace App\Platform\Identity\Federation;

use App\Platform\Identity\OAuth\Signing\SigningCustody;

/**
 * AUTH_RUNTIME (ADR-0020 D6): the role this process is configured as —
 * the same repository running as an independently configured runtime
 * with scoped responsibilities.
 *
 *  - monolith:             everything on one process (default; the
 *                          strangler-era behavior — federation surface
 *                          on the auth host, legacy tenant login for
 *                          apps in direct mode. The HMAC sunset (P8):
 *                          AUTH_MODE=federated closes that window for
 *                          every runtime — one issuer, no second
 *                          identity surface anywhere).
 *  - authorization_server: the issuer. Federated-only with a signing
 *                          key (refused at boot otherwise) and never
 *                          the legacy tenant-scoped login surface —
 *                          the issuer is sole (D1): a second live
 *                          login surface on the issuer is a second
 *                          issuer.
 *  - application:          a business runtime. Never serves the
 *                          federation surface, even when the request
 *                          arrives on the auth host (mispointed vhost
 *                          / copied env must never expose the issuer
 *                          surface); and once federated it also stops
 *                          serving the legacy login surface — the
 *                          cutover is committed.
 *
 * Boot validation is fail-closed (unknown roles and broken pairings
 * refuse to boot — same posture as FederationMode). Surface effects
 * are enforced per request by FederationHostGate (federation scope)
 * and LegacyLoginSurfaceGate (tenant login routes), reading config per
 * request so a canary flip needs no reboot and a misconfiguration
 * degrades to "surface does not exist" (uniform 404), never to a leak.
 */
final class RuntimeRole
{
    public const MONOLITH = 'monolith';

    public const AUTHORIZATION_SERVER = 'authorization_server';

    public const APPLICATION = 'application';

    /**
     * The configured role, validated against the mode and signing-key
     * custody. Intended for the boot-time check; request-time readers
     * use the serves* helpers (config read per request, like the host
     * gate).
     *
     * @throws FederationConfigurationException
     */
    public static function current(): string
    {
        $runtime = (string) config('federation.runtime', self::MONOLITH);

        $signingKeyAvailable = self::signingKeyAvailable();

        if (! $signingKeyAvailable && $runtime === self::AUTHORIZATION_SERVER) {
            // Surface the custody-specific reason (missing file key,
            // incomplete KMS config, not-yet-wired signer) instead of
            // the generic file-custody remedy below.
            SigningCustody::resolve()->assertUsable();
        }

        self::assertValid(
            $runtime,
            (string) config('federation.mode', FederationMode::DIRECT),
            $signingKeyAvailable,
        );

        return $runtime;
    }

    /**
     * @throws FederationConfigurationException
     */
    public static function assertValid(string $runtime, string $mode, bool $signingKeyAvailable = true): void
    {
        if (! in_array($runtime, [self::MONOLITH, self::AUTHORIZATION_SERVER, self::APPLICATION], true)) {
            throw new FederationConfigurationException(
                "AUTH_RUNTIME must be '".self::MONOLITH."', '".self::AUTHORIZATION_SERVER
                ."' or '".self::APPLICATION."', got '{$runtime}'"
            );
        }

        // The issuer runtime exists to mint federated tokens: beside a
        // direct mode it would serve a login surface whose deployment
        // never cut over — broken, not degraded.
        if ($runtime === self::AUTHORIZATION_SERVER && $mode !== FederationMode::FEDERATED) {
            throw new FederationConfigurationException(
                'AUTH_RUNTIME=authorization_server is the issuer: AUTH_MODE must be \''.FederationMode::FEDERATED.'\''
            );
        }

        // No signing key, no issuer — refuse to boot instead of serving
        // a unified login whose tokens can never be minted.
        if ($runtime === self::AUTHORIZATION_SERVER && ! $signingKeyAvailable) {
            throw new FederationConfigurationException(
                'AUTH_RUNTIME=authorization_server requires a token-signing key (PASSPORT_PRIVATE_KEY or the storage oauth-private.key)'
            );
        }
    }

    /**
     * Does this process serve the federation route scope at all? Only
     * an application runtime is excluded — and it is excluded even on
     * the auth host (defense in depth beyond the host match).
     */
    public static function servesFederationSurface(?string $runtime = null): bool
    {
        $runtime ??= (string) config('federation.runtime', self::MONOLITH);

        return $runtime !== self::APPLICATION;
    }

    /**
     * Does this process still serve the legacy tenant-scoped login
     * surface (direct-mode credential routes)? Never on the issuer
     * (D1 sole issuer), never on a federated application runtime
     * (cutover committed), and — the HMAC sunset (P8) — never in a
     * federated deployment at all: flipping AUTH_MODE to `federated`
     * closes the strangler window for every runtime, because the
     * window's whole purpose was to keep legacy logins alive until the
     * BFFs cut over. The monolith keeps the surface in direct mode
     * (both surfaces coexist on one process while the window is open).
     */
    public static function servesLegacyLoginSurface(?string $runtime = null, ?string $mode = null): bool
    {
        $runtime ??= (string) config('federation.runtime', self::MONOLITH);
        $mode ??= (string) config('federation.mode', FederationMode::DIRECT);

        // The HMAC sunset (ADR-0020 D1 cutover): one issuer, no second
        // identity surface anywhere, in any runtime.
        if ($mode === FederationMode::FEDERATED) {
            return false;
        }

        if ($runtime === self::AUTHORIZATION_SERVER) {
            return false;
        }

        return true;
    }

    /**
     * Signing-key custody (D3, custody-aware): the configured custody
     * (AUTH_SIGNING_CUSTODY — file by default, KMS/HSM behind the
     * provider port) must be able to mint. Unusable custody — including
     * a config-valid KMS custody whose remote signer ships later — is
     * treated exactly like a missing key file.
     */
    private static function signingKeyAvailable(): bool
    {
        try {
            SigningCustody::resolve()->assertUsable();

            return true;
        } catch (FederationConfigurationException) {
            return false;
        }
    }
}
