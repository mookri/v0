<?php

namespace Tests\Platform\Identity\Concerns;

use Illuminate\Support\Str;

/**
 * DPoP proof construction for tests (A-SEC-004-9, RFC 9449): real
 * EC P-256 keys through openssl, JOSE ES256 (raw R||S — openssl signs
 * DER, converted here), RFC 7638 thumbprints. Deliberately independent
 * of the production validator: a proof built by this trait that
 * DpopProof accepts (and vice versa) proves interoperability, not a
 * private dialect.
 */
trait BuildsDpopProofs
{
    /**
     * A fresh P-256 keypair with its public JWK (the JWK travels inline
     * in every proof header, per RFC 9449 §4.2).
     *
     * @return array{pem: string, jwk: array<string, string>}
     */
    protected function dpopKey(): array
    {
        // Transient-failure guard (this box, PHP 8.5.6/openssl): a full
        // suite run occasionally sees openssl_pkey_new return false —
        // an entropy-gathering transient, NOT a broken key policy (the
        // same class passes 21/21 standalone and 49/49 filtered; the
        // full-suite flakes all point at this one line, at DIFFERENT
        // tests, which a behavioral regression could not do). Retry a
        // bounded number of times, then fail loudly with the LAST
        // openssl error — a persistently failing keygen still fails
        // the test exactly as before, nothing is masked.
        $key = false;
        $opensslConfig = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ];
        if (file_exists('C:\xampp\php\extras\ssl\openssl.cnf')) {
            $opensslConfig['config'] = 'C:\xampp\php\extras\ssl\openssl.cnf';
        }

        for ($attempt = 0; $attempt < 3 && $key === false; $attempt++) {
            if ($attempt > 0) {
                usleep(50_000);
            }

            $key = openssl_pkey_new($opensslConfig);
        }

        if ($key === false) {
            $this->fail('openssl could not generate a DPoP test key: '.(string) openssl_error_string());
        }

        $privatePem = '';

        if (! openssl_pkey_export($key, $privatePem, null, $opensslConfig)) {
            $this->fail('openssl could not export the DPoP test private key.');
        }

        $details = openssl_pkey_get_details($key);
        $x = is_array($details) ? (string) ($details['ec']['x'] ?? '') : '';
        $y = is_array($details) ? (string) ($details['ec']['y'] ?? '') : '';

        // Coordinates arrive raw (32 bytes) on this PHP build and as
        // hex (64 chars) on others — normalize to raw by width, since
        // the two lengths can never collide for P-256.
        if (strlen($x) === 64 && ctype_xdigit($x)) {
            $x = (string) hex2bin($x);
        }

        if (strlen($y) === 64 && ctype_xdigit($y)) {
            $y = (string) hex2bin($y);
        }

        if (strlen($x) !== 32 || strlen($y) !== 32) {
            $this->fail('openssl returned malformed EC coordinates for the DPoP test key.');
        }

        return [
            'pem' => $privatePem,
            'jwk' => [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => $this->dpopB64($x),
                'y' => $this->dpopB64($y),
            ],
        ];
    }

    /**
     * Mint a proof for one request leg.
     *
     * @param  array{pem: string, jwk: array<string, string>}  $key  the signing key
     * @param  array<string, mixed>  $overrides  claim overrides for the rejection cases (stale iat, known jti for replay)
     * @param  array<string, string>|null  $advertisedJwk  advertise a DIFFERENT public key while signing with $key (signature-mismatch case)
     */
    protected function dpopProof(array $key, string $htm, string $htu, array $overrides = [], ?string $accessToken = null, ?array $advertisedJwk = null): string
    {
        $header = [
            'typ' => 'DPoP',
            'alg' => 'ES256',
            'jwk' => $advertisedJwk ?? $key['jwk'],
        ];

        $payload = array_merge([
            'htm' => $htm,
            'htu' => $htu,
            'iat' => time(),
            'jti' => (string) Str::uuid(),
        ], $overrides);

        if ($accessToken !== null) {
            $payload['ath'] = $this->dpopB64(hash('sha256', $accessToken, true));
        }

        $data = $this->dpopB64((string) json_encode($header)).'.'.$this->dpopB64((string) json_encode($payload));

        if (! openssl_sign($data, $signature, $key['pem'], OPENSSL_ALGO_SHA256)) {
            $this->fail('openssl could not sign the DPoP proof.');
        }

        return $data.'.'.$this->dpopB64($this->ecdsaDerToRaw($signature));
    }

    /**
     * RFC 7638 thumbprint of a proof JWK — what the token's cnf must
     * equal (the test's independent computation of the binding).
     *
     * @param  array<string, string>  $jwk
     */
    protected function dpopThumbprint(array $jwk): string
    {
        $canonical = json_encode([
            'crv' => $jwk['crv'],
            'kty' => $jwk['kty'],
            'x' => $jwk['x'],
            'y' => $jwk['y'],
        ]);

        return $this->dpopB64(hash('sha256', (string) $canonical, true));
    }

    /** openssl DER ECDSA signature → JOSE raw R||S (P-256: 2×32 bytes). */
    private function ecdsaDerToRaw(string $der): string
    {
        // Deterministic openssl output: 30 <short len> 02 <rlen> r 02 <slen> s.
        if (strlen($der) < 8 || ord($der[0]) !== 0x30 || ord($der[2]) !== 0x02) {
            $this->fail('Unexpected DER shape from openssl for the DPoP proof signature.');
        }

        $rLen = ord($der[3]);
        $r = substr($der, 4, $rLen);
        $sOffset = 4 + $rLen;

        if (ord($der[$sOffset]) !== 0x02) {
            $this->fail('Unexpected DER shape from openssl for the DPoP proof signature.');
        }

        $s = substr($der, $sOffset + 2, ord($der[$sOffset + 1]));

        return str_pad(substr($r, -32), 32, "\x00", STR_PAD_LEFT)
            .str_pad(substr($s, -32), 32, "\x00", STR_PAD_LEFT);
    }

    private function dpopB64(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
