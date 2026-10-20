<?php

namespace App\Platform\Identity\Federation;

/**
 * AUTH_MODE (ADR-0020): the one knob that tells applications which
 * login path exists.
 *
 *  - direct:    apps keep calling their own tenant-scoped login APIs;
 *               the federation surface (if configured) serves
 *               metadata/probe only.
 *  - federated: apps redirect browsers to the auth subdomain's unified
 *               login; the OAuth authorize -> code -> back-channel
 *               exchange continues from there.
 *
 * Validated fail-closed at boot (AppServiceProvider::register) so a
 * typo can never boot a deployment where apps guess against a surface
 * that cannot serve them.
 */
final class FederationMode
{
    public const DIRECT = 'direct';

    public const FEDERATED = 'federated';

    /**
     * The configured mode, validated against the rest of the federation
     * config. Intended for the boot-time check; request-time readers
     * should use config('federation.mode') directly (the gate reads
     * config per request so canary flips need no reboot).
     *
     * @throws FederationConfigurationException
     */
    public static function current(): string
    {
        $mode = (string) config('federation.mode', self::DIRECT);

        self::assertValid(
            $mode,
            (string) config('federation.domain', ''),
            (string) config('federation.issuer', ''),
        );

        return $mode;
    }

    /**
     * @throws FederationConfigurationException
     */
    public static function assertValid(string $mode, string $domain = '', string $issuer = ''): void
    {
        if (! in_array($mode, [self::DIRECT, self::FEDERATED], true)) {
            throw new FederationConfigurationException(
                "AUTH_MODE must be '".self::DIRECT."' or '".self::FEDERATED."', got '{$mode}'"
            );
        }

        // Federated apps redirect browsers to the auth subdomain: a
        // deployment without a domain (nothing answers the redirect) or
        // an issuer (no pinned `iss` to verify tokens against) is
        // broken, not degraded — refuse to boot.
        if ($mode === self::FEDERATED && ($domain === '' || $issuer === '')) {
            throw new FederationConfigurationException(
                'AUTH_MODE=federated requires AUTH_DOMAIN and AUTH_ISSUER to be configured'
            );
        }
    }
}
