<?php

namespace App\Platform\Identity\OAuth;

/**
 * JWK rendering for RSA public keys (ADR-0020 D3): every published key
 * carries an RFC 7638 thumbprint `kid`, so JWKS consumers can correlate,
 * cache and detect rotation — and `auth:rotate-signing-key` can archive
 * a retired public half under the exact id it published. Pure function
 * over PEM input; never touches private material.
 */
final class Jwk
{
    /**
     * @return array{kty: string, use: string, alg: string, kid: string, n: string, e: string}|null
     *         null when the PEM is not a readable RSA public key
     */
    public static function fromPem(string $publicPem): ?array
    {
        $rsa = self::rsa($publicPem);

        if ($rsa === null) {
            return null;
        }

        $n = self::base64UrlEncode($rsa['n']);
        $e = self::base64UrlEncode($rsa['e']);

        return [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => self::thumbprint($n, $e),
            'n' => $n,
            'e' => $e,
        ];
    }

    /**
     * RFC 7638 JWK thumbprint (SHA-256, base64url) of a PEM public key.
     */
    public static function thumbprintFromPem(string $publicPem): ?string
    {
        $rsa = self::rsa($publicPem);

        if ($rsa === null) {
            return null;
        }

        return self::thumbprint(
            self::base64UrlEncode($rsa['n']),
            self::base64UrlEncode($rsa['e'])
        );
    }

    /**
     * Canonical thumbprint input: required members only, lexicographic
     * member order ("e", "kty", "n") — RFC 7638 §3.
     */
    private static function thumbprint(string $n, string $e): string
    {
        $canonical = json_encode(
            ['e' => $e, 'kty' => 'RSA', 'n' => $n],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return self::base64UrlEncode(hash('sha256', (string) $canonical, true));
    }

    /**
     * @return array{n: string, e: string}|null
     */
    private static function rsa(string $publicPem): ?array
    {
        if (trim($publicPem) === '') {
            return null;
        }

        $key = @openssl_pkey_get_public($publicPem);

        if ($key === false) {
            return null;
        }

        $details = openssl_pkey_get_details($key);

        if ($details === false
            || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA
            || ! isset($details['rsa']['n'], $details['rsa']['e'])
        ) {
            return null;
        }

        return ['n' => $details['rsa']['n'], 'e' => $details['rsa']['e']];
    }

    private static function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
