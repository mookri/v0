<?php

namespace App\Platform\Identity\OAuth;

/**
 * RSA key-pair generation for key administration (ADR-0020 D3): client
 * key provisioning and the signing-key rotation drill.
 *
 * Windows PHP distributions frequently ship without an OpenSSL config
 * file, and on such machines `openssl_pkey_new` / `openssl_pkey_export`
 * fail outright (OpenSSL cannot resolve its defaults). Generation here
 * falls back to a minimal generated config so rotation and provisioning
 * behave identically everywhere — the config only seeds OpenSSL
 * defaults; it never carries or resembles key material.
 */
final class RsaKeyGenerator
{
    /**
     * @return array{private: string, public: string}|null null when generation failed
     */
    public static function generate(int $bits = 4096): ?array
    {
        foreach ([null, self::minimalConfigPath()] as $config) {
            $options = [
                'private_key_bits' => $bits,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];

            if ($config !== null) {
                $options['config'] = $config;
            }

            $resource = openssl_pkey_new($options);

            if ($resource === false) {
                continue;
            }

            $exportOptions = $config !== null ? ['config' => $config] : [];

            if (! openssl_pkey_export($resource, $private, null, $exportOptions)
                || trim((string) $private) === ''
            ) {
                continue;
            }

            $details = openssl_pkey_get_details($resource);

            if ($details === false || trim((string) ($details['key'] ?? '')) === '') {
                continue;
            }

            return ['private' => $private, 'public' => (string) $details['key']];
        }

        return null;
    }

    private static function minimalConfigPath(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'navdhya-openssl.cnf';

        // Rewritten every call: a stale or half-written file from a
        // concurrent process must never poison later generations.
        @file_put_contents($path, "[req]\ndistinguished_name = req_distinguished_name\nprompt = no\n[req_distinguished_name]\nCN = navdhya\n");

        return $path;
    }
}
