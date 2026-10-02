<?php

namespace App\Platform\Identity\OAuth;

use Laravel\Passport\Passport;

/**
 * Shared OAuth token resolution (revocation + introspection): access
 * tokens travel as JWTs (identify by jti), refresh tokens travel
 * league-encrypted (decrypt to the stored id first). Unknown or
 * undecryptable input resolves to null — callers answer without
 * oracles from there.
 */
trait ResolvesOAuthTokens
{
    private function findAccessToken(string $token): mixed
    {
        return Passport::token()->newQuery()->whereKey($this->jtiOf($token))->first();
    }

    private function findRefreshToken(string $token): mixed
    {
        $id = $this->refreshTokenId($token);

        if ($id === null) {
            return null;
        }

        return Passport::refreshToken()->newQuery()->whereKey($id)->first();
    }

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
