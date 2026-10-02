<?php

namespace App\Platform\Identity\OAuth\Http\Controllers;

use App\Platform\Identity\OAuth\ResolvesOAuthTokens;
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
    use ResolvesOAuthTokens;

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
            $access = $this->findAccessToken($token);

            if ($access !== null) {
                Passport::token()->newQuery()->whereKey($access->getKey())->update(['revoked' => true]);
                Passport::refreshToken()->newQuery()->where('access_token_id', $access->getKey())->update(['revoked' => true]);

                return;
            }
        }

        if ($hint === null || $hint === 'refresh_token') {
            // Refresh tokens travel encrypted: the trait decrypts to the
            // stored id first; undecryptable input is an unknown token
            // (silent 200, no oracle).
            $refresh = $this->findRefreshToken($token);

            if ($refresh !== null) {
                Passport::refreshToken()->newQuery()->whereKey($refresh->getKey())->update(['revoked' => true]);
                Passport::token()->newQuery()->whereKey($refresh->getAttribute('access_token_id'))->update(['revoked' => true]);
            }
        }
    }
}
