<?php

namespace App\Platform\Identity;

/**
 * Passport access-token verification (P3b dual-accept window): RS256
 * signature against our public key (config or generated key file),
 * expiry enforced, shape-validated. Revocation and client checks live
 * with the caller (DB rows), like ActorTokenVerifier splits crypto
 * from linkage. Hand-rolled openssl on purpose — same house style as
 * the HMAC verifier, zero new dependencies.
 *
 * A-SEC-004-9: a present `cnf` confirmation claim must be exactly
 * `{dpop_jkt: non-empty string}` — a token whose DPoP binding is
 * malformed is rejected fail-closed rather than silently downgraded
 * to an unconstrained bearer.
 */
final class PassportJwtVerifier
{
    /**
     * Memoised verification key.
     *
     * Re-parsing the PEM through OpenSSL on every token verification was
     * the single hottest cost on the authenticated request path. The
     * cache is keyed on the *content* of the key, so a signing-key
     * rotation invalidates it immediately instead of serving a stale
     * key (a timestamp/size fingerprint could miss a same-second,
     * same-size rewrite).
     */
    private static ?string $cachedFingerprint = null;

    /** @var \OpenSSLAsymmetricKey|false|null */
    private static mixed $cachedKeyObject = null;

    /**
     * @return array{jti: string, sub: string, aud: string, exp: int, scopes: list<string>, cnf: array{dpop_jkt: string}|null}|null
     */
    public static function verify(string $token, int $now): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
            return null;
        }

        [$encodedHeader, $encodedClaims, $encodedSignature] = $parts;

        $header = json_decode(self::base64UrlDecode($encodedHeader), true);
        if (! is_array($header) || ($header['alg'] ?? null) !== 'RS256' || ($header['typ'] ?? null) !== 'JWT') {
            return null;
        }

        $key = self::publicKey();

        if ($key === '') {
            return null;
        }

        $signature = self::base64UrlDecode($encodedSignature);
        $verificationKey = self::verificationKey($key);
        $verified = openssl_verify(
            "{$encodedHeader}.{$encodedClaims}",
            $signature,
            $verificationKey === false ? $key : $verificationKey,
            OPENSSL_ALGO_SHA256,
        );

        if ($verified !== 1) {
            return null;
        }

        $claims = json_decode(self::base64UrlDecode($encodedClaims), true);

        if (! is_array($claims)) {
            return null;
        }

        $jti = $claims['jti'] ?? null;
        $sub = $claims['sub'] ?? null;
        $aud = $claims['aud'] ?? null;
        $exp = $claims['exp'] ?? null;

        if (! is_string($jti) || $jti === ''
            || (! is_string($sub) && ! is_int($sub))
            || (! is_string($aud) && ! is_array($aud))
            || ! is_numeric($exp)) {
            return null;
        }

        if ((int) $exp <= $now) {
            return null;
        }

        $scopes = $claims['scopes'] ?? [];
        if (! is_array($scopes)) {
            return null;
        }

        // A-SEC-004-9: present-but-malformed DPoP binding → reject the
        // whole token fail-closed (never an unconstrained bearer).
        $cnf = null;

        if (array_key_exists('cnf', $claims)) {
            $rawCnf = $claims['cnf'];

            if (! is_array($rawCnf)
                || ! is_string($rawCnf['dpop_jkt'] ?? null)
                || $rawCnf['dpop_jkt'] === '') {
                return null;
            }

            $cnf = ['dpop_jkt' => $rawCnf['dpop_jkt']];
        }

        return [
            'jti' => $jti,
            'sub' => (string) $sub,
            'aud' => is_array($aud) ? (string) ($aud[0] ?? '') : $aud,
            'exp' => (int) $exp,
            'scopes' => array_values(array_filter($scopes, 'is_string')),
            'cnf' => $cnf,
        ];
    }

    private static function publicKey(): string
    {
        $configured = (string) config('passport.public_key', '');

        if ($configured !== '') {
            return $configured;
        }

        $path = storage_path('oauth-public.key');

        if (! is_file($path)) {
            return '';
        }

        $contents = file_get_contents($path);

        return is_string($contents) ? $contents : '';
    }

    /**
     * Parse the PEM once and keep the OpenSSL key object.
     *
     * `openssl_verify()` accepts a parsed key, which skips re-parsing
     * the PEM on every call. The parsed object is cached against the
     * content of the key, so a rotation drops it.
     */
    private static function verificationKey(string $pem): mixed
    {
        $fingerprint = hash('sha256', $pem);

        if (self::$cachedFingerprint === $fingerprint && self::$cachedKeyObject !== null) {
            return self::$cachedKeyObject;
        }

        $resource = openssl_pkey_get_public($pem);

        self::$cachedFingerprint = $fingerprint;
        self::$cachedKeyObject = $resource === false ? false : $resource;

        return self::$cachedKeyObject;
    }

    private static function base64UrlDecode(string $segment): string
    {
        $decoded = base64_decode(strtr($segment, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}
