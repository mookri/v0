<?php

namespace App\Platform\Identity\OAuth\Dpop;

use Illuminate\Support\Facades\Cache;

/**
 * RFC 9449 DPoP proof verification (A-SEC-004-9), house style: the
 * hand-rolled openssl posture of PassportJwtVerifier, zero new
 * dependencies. Two algorithms — ES256 (the BFF proof key) and RS256
 * (other first-party tooling) — advertised together in the resource
 * challenge's `algs` parameter.
 *
 * validate() is the ONE implementation: the token endpoint calls it to
 * BIND a mint, resource endpoints call it to ENFORCE a bound token, so
 * htm/htu/iat/jti/ath rules can never drift between issuing and
 * consuming. It returns the RFC 7638 JWK thumbprint (jkt) the token
 * binds to, or throws DpopProofInvalidException with a stable reason.
 *
 * Replay: an otherwise-valid proof must consume its single-use jti
 * (Cache::add, TTL >2× the iat skew window). On a persistent store
 * (database/redis/file — the deployment default) that constrains
 * replays across requests; on the array store (phpunit) it constrains
 * them within the test process — the same per-store posture as every
 * other cache-backed guard in this codebase.
 */
final class DpopProof
{
    /** Accepted signing algorithms (also the resource `algs` challenge). */
    public const ALGORITHMS = 'ES256, RS256';

    private const IAT_SKEW_SECONDS = 60;

    private const JTI_TTL_SECONDS = 300;

    /**
     * Validate the DPoP proof for one request leg.
     *
     * @param  string  $proof  the raw `DPoP` header value
     * @param  string  $method  the HTTP method of the request being proved
     * @param  string  $requestUri  the request URI the proof targets (query/fragment ignored, per RFC 9449 §4.2 htu)
     * @param  string|null  $accessToken  the presented access token when enforcing a resource leg (enables the `ath` check); null at the token endpoint
     * @return string the RFC 7638 thumbprint (jkt) of the proof's public key
     *
     * @throws DpopProofInvalidException
     */
    public static function validate(string $proof, string $method, string $requestUri, ?string $accessToken): string
    {
        $segments = explode('.', $proof);

        if (count($segments) !== 3 || $segments[0] === '' || $segments[1] === '' || $segments[2] === '') {
            throw new DpopProofInvalidException('The DPoP proof is malformed.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;

        $header = self::decodeObject($encodedHeader, 'header');

        if (($header['typ'] ?? null) !== 'DPoP') {
            throw new DpopProofInvalidException('The DPoP proof header must be typ=DPoP.');
        }

        $alg = $header['alg'] ?? null;

        if (! is_string($alg) || ! in_array($alg, ['ES256', 'RS256'], true)) {
            throw new DpopProofInvalidException('The DPoP proof uses an unsupported signing algorithm.');
        }

        $jwk = $header['jwk'] ?? null;

        if (! is_array($jwk)) {
            throw new DpopProofInvalidException('The DPoP proof header must carry the public key as a JWK.');
        }

        $publicKey = self::publicKeyFromJwk($jwk);
        $signature = self::base64UrlDecode($encodedSignature);

        if ($signature === '') {
            throw new DpopProofInvalidException('The DPoP proof signature is not base64url.');
        }

        if ($alg === 'ES256') {
            // JOSE carries ECDSA as raw R||S; openssl verifies DER.
            if (strlen($signature) !== 64) {
                throw new DpopProofInvalidException('The DPoP proof signature has the wrong length for ES256.');
            }

            $signature = self::ecdsaRawToDer($signature);
        }

        if (openssl_verify("{$encodedHeader}.{$encodedPayload}", $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            throw new DpopProofInvalidException('The DPoP proof signature does not verify.');
        }

        $payload = self::decodeObject($encodedPayload, 'payload');

        $htm = $payload['htm'] ?? null;

        if (! is_string($htm) || strcasecmp($htm, $method) !== 0) {
            throw new DpopProofInvalidException('The DPoP proof htm does not match the request method.');
        }

        $htu = $payload['htu'] ?? null;
        $normalized = is_string($htu) ? self::normalizeHtu($htu) : '';
        $expected = self::normalizeHtu($requestUri);

        if ($normalized === '' || $normalized !== $expected) {
            throw new DpopProofInvalidException('The DPoP proof htu does not match the request URI.');
        }

        $iat = $payload['iat'] ?? null;

        if (! is_int($iat)) {
            throw new DpopProofInvalidException('The DPoP proof iat must be a unix timestamp.');
        }

        if (abs(time() - $iat) > self::IAT_SKEW_SECONDS) {
            throw new DpopProofInvalidException('The DPoP proof iat is outside the accepted time window.');
        }

        $jti = $payload['jti'] ?? null;

        if (! is_string($jti) || $jti === '') {
            throw new DpopProofInvalidException('The DPoP proof jti must be a non-empty string.');
        }

        if ($accessToken !== null) {
            $ath = $payload['ath'] ?? null;
            $expectedAth = self::base64UrlEncode(hash('sha256', $accessToken, true));

            if (! is_string($ath) || ! hash_equals($expectedAth, $ath)) {
                throw new DpopProofInvalidException('The DPoP proof ath does not match the access token.');
            }
        }

        // Replay restraint runs LAST so only fully valid proofs spend
        // their jti: a proof rejected for any reason above leaves the
        // nonce unconsumed, and a second genuinely-valid presentation
        // of the same jti is denied here.
        if (! Cache::add('dpop:proof:'.$jti, 1, self::JTI_TTL_SECONDS)) {
            throw new DpopProofInvalidException('The DPoP proof jti has already been used (replay).');
        }

        return self::thumbprint($jwk);
    }

    /**
     * RFC 7638 JWK thumbprint over the required members only, in
     * lexicographic order, SHA-256, base64url.
     *
     * @param  array<string, mixed>  $jwk
     *
     * @throws DpopProofInvalidException
     */
    private static function thumbprint(array $jwk): string
    {
        $required = match ($jwk['kty'] ?? null) {
            'EC' => ['crv', 'kty', 'x', 'y'],
            'RSA' => ['e', 'kty', 'n'],
            default => throw new DpopProofInvalidException('The DPoP proof JWK has no supported key type.'),
        };

        $canonical = '';

        foreach ($required as $member) {
            $value = $jwk[$member] ?? null;

            if (! is_string($value) || $value === '') {
                throw new DpopProofInvalidException('The DPoP proof JWK is missing required members.');
            }

            $canonical .= ($canonical === '' ? '' : ',')
                .(string) json_encode($member)
                .':'
                .(string) json_encode($value);
        }

        // RFC 7638 §3.2: the hash input is the JSON object itself —
        // braces included.
        return self::base64UrlEncode(hash('sha256', '{'.$canonical.'}', true));
    }

    /**
     * JWK → PEM SPKI (the DER is built by hand: fixed AlgorithmIdentifier
     * prefixes + the raw key material, no new dependencies).
     *
     * @param  array<string, mixed>  $jwk
     *
     * @throws DpopProofInvalidException
     */
    private static function publicKeyFromJwk(array $jwk): string
    {
        $kty = $jwk['kty'] ?? null;

        if ($kty === 'EC') {
            if (($jwk['crv'] ?? null) !== 'P-256') {
                throw new DpopProofInvalidException('Only the P-256 EC curve is supported for DPoP proofs.');
            }

            $x = is_string($jwk['x'] ?? null) ? self::base64UrlDecode($jwk['x']) : '';
            $y = is_string($jwk['y'] ?? null) ? self::base64UrlDecode($jwk['y']) : '';

            if (strlen($x) !== 32 || strlen($y) !== 32) {
                throw new DpopProofInvalidException('The DPoP proof EC public key coordinates are malformed.');
            }

            $prefix = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');

            if ($prefix === false) {
                throw new DpopProofInvalidException('The DPoP proof EC public key could not be built.');
            }

            return self::pemPublicKey($prefix."\x04".$x.$y);
        }

        if ($kty === 'RSA') {
            $n = is_string($jwk['n'] ?? null) ? self::base64UrlDecode($jwk['n']) : '';
            $e = is_string($jwk['e'] ?? null) ? self::base64UrlDecode($jwk['e']) : '';
            $n = ltrim($n, "\x00");

            if ($n === '' || $e === '') {
                throw new DpopProofInvalidException('The DPoP proof RSA public key is malformed.');
            }

            $rsaKey = self::derSequence(self::derInteger($n).self::derInteger($e));
            $algorithmId = hex2bin('300d06092a864886f70d0101010500');

            if ($algorithmId === false) {
                throw new DpopProofInvalidException('The DPoP proof RSA public key could not be built.');
            }

            $bitString = "\x03".self::derLength(strlen($rsaKey) + 1)."\x00".$rsaKey;

            return self::pemPublicKey(self::derSequence($algorithmId.$bitString));
        }

        throw new DpopProofInvalidException(
            sprintf('The DPoP proof JWK key type %s is not supported.', is_string($kty) ? $kty : '(unknown)')
        );
    }

    /** JOSE raw R||S → DER for openssl (P-256: two 32-byte integers). */
    private static function ecdsaRawToDer(string $raw): string
    {
        $content = self::derInteger(substr($raw, 0, 32)).self::derInteger(substr($raw, 32, 32));

        return "\x30".self::derLength(strlen($content)).$content;
    }

    private static function derInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");

        if ($bytes === '') {
            $bytes = "\x00";
        }

        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00".$bytes;
        }

        return "\x02".self::derLength(strlen($bytes)).$bytes;
    }

    private static function derSequence(string $content): string
    {
        return "\x30".self::derLength(strlen($content)).$content;
    }

    private static function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        if ($length <= 0xFF) {
            return "\x81".chr($length);
        }

        return "\x82".pack('n', $length);
    }

    private static function pemPublicKey(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END PUBLIC KEY-----";
    }

    /**
     * RFC 9449 §4.2 htu: scheme://host[:port]/path — lowercased
     * authority, default ports elided, query and fragment never.
     * Returns '' when the URI cannot be parsed (never equal to a
     * well-formed normalized URI).
     */
    private static function normalizeHtu(string $uri): string
    {
        $parts = parse_url($uri);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port'])
            && ! (($scheme === 'http' && $parts['port'] === 80) || ($scheme === 'https' && $parts['port'] === 443))
            ? ':'.$parts['port']
            : '';

        return $scheme.'://'.$host.$port.($parts['path'] ?? '');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws DpopProofInvalidException
     */
    private static function decodeObject(string $segment, string $part): array
    {
        $decoded = self::base64UrlDecode($segment);

        if ($decoded === '') {
            throw new DpopProofInvalidException("The DPoP proof {$part} is not base64url.");
        }

        $json = json_decode($decoded, true);

        if (! is_array($json) || array_is_list($json)) {
            throw new DpopProofInvalidException("The DPoP proof {$part} is not a JSON object.");
        }

        /** @var array<string, mixed> $json */
        return $json;
    }

    private static function base64UrlDecode(string $segment): string
    {
        $decoded = base64_decode(strtr($segment, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    private static function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
