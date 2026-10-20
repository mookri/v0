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
    /**
     * Protocol-surface validation: failures answer league-shaped
     * `invalid_request` 400, never the kernel 422 envelope (this is
     * protocol surface — clients speak RFC 6749 here, not our API).
     *
     * @param  array<string, mixed> $rules
     * @return array{array<string, mixed>|null, \Illuminate\Http\JsonResponse|null}
     */
    private function validateProtocol(\Illuminate\Http\Request $request, array $rules): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $description = 'The request is missing a required parameter: '.implode(', ', $validator->errors()->keys()).'.';

            return [null, new \Illuminate\Http\JsonResponse([
                'error' => 'invalid_request',
                'error_description' => $description,
                'message' => $description,
            ], 400)];
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();

        return [$validated, null];
    }

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
