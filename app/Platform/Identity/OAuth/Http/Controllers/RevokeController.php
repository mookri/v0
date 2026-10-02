<?php

namespace App\Platform\Identity\OAuth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

/**
 * RFC 7009 token revocation (SEC-015 §8.1): client-authenticated
 * revocation of access and refresh tokens. Revoking an access token
 * also kills its refresh tokens (else refresh resurrects the session);
 * revoking a refresh token kills its access token too (symmetric kill).
 * Unknown tokens still answer 200 (RFC §2.2.1 — no oracle). Errors use
 * league's shape, not the kernel envelope: this is protocol surface.
 * Confidential clients only in v1.
 */
final class RevokeController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'token_type_hint' => ['sometimes', 'string', 'in:access_token,refresh_token'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        $client = Client::query()->whereKey($validated['client_id'])->first();

        if ($client === null
            || (bool) $client->getAttribute('revoked')
            || $client->getAttribute('secret') === null
            || ! Hash::check($validated['client_secret'], (string) $client->getAttribute('secret'))) {
            return new JsonResponse([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
                'message' => 'Client authentication failed.',
            ], 401);
        }

        $this->revoke($validated['token'], $validated['token_type_hint'] ?? null);

        return new JsonResponse((object) [], 200);
    }

    private function revoke(string $token, ?string $hint): void
    {
        if ($hint === null || $hint === 'access_token') {
            $jti = $this->jtiOf($token);
            $accessIds = Passport::token()->newQuery()
                ->whereKey($jti)
                ->pluck('id');

            if ($accessIds->isNotEmpty()) {
                Passport::token()->newQuery()->whereIn('id', $accessIds)->update(['revoked' => true]);
                Passport::refreshToken()->newQuery()->whereIn('access_token_id', $accessIds)->update(['revoked' => true]);

                return;
            }
        }

        if ($hint === null || $hint === 'refresh_token') {
            // Refresh tokens travel encrypted (league defuse payload keyed
            // by the app key): decrypt to the stored id first. Undecryptable
            // input is an unknown token — silent 200, no oracle.
            $id = $this->refreshTokenId($token);

            if ($id === null) {
                return;
            }

            $refresh = Passport::refreshToken()->newQuery()->whereKey($id)->first();

            if ($refresh !== null) {
                Passport::refreshToken()->newQuery()->whereKey($id)->update(['revoked' => true]);
                Passport::token()->newQuery()->whereKey($refresh->getAttribute('access_token_id'))->update(['revoked' => true]);
            }
        }
    }

    /**
     * Access tokens travel as JWTs (revoke by jti); anything else passes
     * through for the refresh path to attempt.
     */
    private function jtiOf(string $token): string
    {
        if (substr_count($token, '.') !== 2) {
            return $token;
        }

        $claims = json_decode(base64_decode(strtr(explode('.', $token)[1], '-_', '+/'), true) ?: '[]', true);

        return is_array($claims) && isset($claims['jti']) && is_string($claims['jti']) ? $claims['jti'] : $token;
    }

    private function refreshTokenId(string $token): ?string
    {
        try {
            $payload = json_decode(
                \Defuse\Crypto\Crypto::decryptWithPassword($token, app('encrypter')->getKey()),
                true
            );
        } catch (\Throwable) {
            return null;
        }

        return is_array($payload) && isset($payload['refresh_token_id']) && is_string($payload['refresh_token_id'])
            ? $payload['refresh_token_id']
            : null;
    }
}
