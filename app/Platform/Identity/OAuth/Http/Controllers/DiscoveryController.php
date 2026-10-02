<?php

namespace App\Platform\Identity\OAuth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OAuth/OIDC discovery surfaces (SEC-002 §2.1, partial): RFC 8414
 * authorization-server metadata, JWKS public keys, and a userinfo
 * endpoint. Deliberately NOT `openid-configuration` yet — OIDC
 * discovery implies `id_token` issuance, which lands separately; the
 * RFC 8414 document carries everything an OAuth client needs today.
 * JWKS publishes the active RSA key without `kid` correlation (league
 * mints kid-less tokens; rotation dual-publishes when the time comes).
 */
final class DiscoveryController
{
    public function metadata(): JsonResponse
    {
        $base = rtrim((string) config('app.url'), '/');

        return new JsonResponse([
            'issuer' => $base,
            'authorization_endpoint' => $base.'/oauth/authorize',
            'token_endpoint' => $base.'/oauth/token',
            'userinfo_endpoint' => $base.'/oauth/userinfo',
            'jwks_uri' => $base.'/.well-known/jwks.json',
            'revocation_endpoint' => $base.'/api/oauth/revoke',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token', 'client_credentials'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'code_challenge_methods_supported' => ['S256'],
        ], 200, ['Cache-Control' => 'public, max-age=3600']);
    }

    public function jwks(): JsonResponse
    {
        $key = self::publicKey();

        if ($key === '') {
            return new JsonResponse([
                'error' => ['code' => 'service_unavailable', 'message' => 'Signing keys unavailable.'],
            ], 503);
        }

        $details = openssl_pkey_get_details(openssl_pkey_get_public($key));

        if ($details === false || ($details['rsa'] ?? null) === null) {
            return new JsonResponse([
                'error' => ['code' => 'internal_error', 'message' => 'Signing keys unavailable.'],
            ], 500);
        }

        return new JsonResponse([
            'keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
                'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
            ]],
        ], 200, ['Cache-Control' => 'public, max-age=3600']);
    }

    public function userinfo(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            // Machine principals hold no profile: userinfo is identity
            // surface for users (humans via any accepted credential).
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.'],
            ], 401);
        }

        return new JsonResponse([
            'sub' => $user->external_id !== null && $user->external_id !== ''
                ? (string) $user->external_id
                : (string) $user->getAuthIdentifier(),
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
            'phone_number' => $user->phone,
            'phone_number_verified' => $user->phone_verified_at !== null,
        ]);
    }

    private static function publicKey(): string
    {
        $configured = (string) config('passport.public_key', '');

        if ($configured !== '') {
            return $configured;
        }

        $path = storage_path('oauth-public.key');

        if (is_file($path)) {
            $contents = file_get_contents($path);

            return is_string($contents) ? $contents : '';
        }

        return '';
    }
}
