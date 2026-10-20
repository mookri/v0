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
 *
 * Ownership (RFC 7009 §2.1/§5, 9c): after client authentication the
 * server verifies the token was ISSUED TO THAT CLIENT; a token that
 * resolves but belongs to another client is refused (400
 * invalid_grant) and NOT revoked — revocation is not a cross-client
 * kill switch. The refusal is loud on purpose (ratified D-9c): only a
 * party holding the full token value ever sees it, and RFC §5 weighs
 * that guessing scenario as already-catastrophic.
 */
final class RevokeController
{
    use ResolvesOAuthTokens;

    public function __invoke(Request $request): JsonResponse
    {
        [$validated, $denial] = $this->validateProtocol($request, [
            'token' => ['required', 'string'],
            'token_type_hint' => ['sometimes', 'string', 'in:access_token,refresh_token'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        if ($denial !== null) {
            return $denial;
        }

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

        $denial = $this->revoke($validated['token'], $validated['token_type_hint'] ?? null, (string) $validated['client_id']);

        if ($denial !== null) {
            return $denial;
        }

        return new JsonResponse((object) [], 200);
    }

    /**
     * @return JsonResponse|null a league-shaped refusal, or null on success/unknown
     */
    private function revoke(string $token, ?string $hint, string $clientId): ?JsonResponse
    {
        if ($hint === null || $hint === 'access_token') {
            $access = $this->findAccessToken($token);

            if ($access !== null) {
                if ((string) $access->getAttribute('client_id') !== $clientId) {
                    return self::foreignToken();
                }

                Passport::token()->newQuery()->whereKey($access->getKey())->update(['revoked' => true]);
                Passport::refreshToken()->newQuery()->where('access_token_id', $access->getKey())->update(['revoked' => true]);

                // D2: local revocations invalidate the status cache
                // immediately (bound degrades to 0 s on this node).
                app(\App\Platform\Identity\Federation\RevocationStatusCache::class)
                    ->invalidateTokens((string) $access->getKey());

                $this->recordRevocation((string) $access->getAttribute('user_id'), $clientId, (string) $access->getKey());

                return null;
            }
        }

        if ($hint === null || $hint === 'refresh_token') {
            // Refresh tokens travel encrypted: the trait decrypts to the
            // stored id first; undecryptable input is an unknown token
            // (silent 200, no oracle).
            $refresh = $this->findRefreshToken($token);

            if ($refresh !== null) {
                // Ownership resolves through the refresh token's related
                // access token (the row carries the minting client).
                $owner = Passport::token()->newQuery()
                    ->whereKey($refresh->getAttribute('access_token_id'))
                    ->first();

                if ($owner === null || (string) $owner->getAttribute('client_id') !== $clientId) {
                    return self::foreignToken();
                }

                Passport::refreshToken()->newQuery()->whereKey($refresh->getKey())->update(['revoked' => true]);
                Passport::token()->newQuery()->whereKey($refresh->getAttribute('access_token_id'))->update(['revoked' => true]);

                app(\App\Platform\Identity\Federation\RevocationStatusCache::class)
                    ->invalidateTokens((string) $refresh->getAttribute('access_token_id'));

                $this->recordRevocation((string) $owner->getAttribute('user_id'), $clientId, (string) $owner->getKey());
            }
        }

        return null;
    }

    private static function foreignToken(): JsonResponse
    {
        return new JsonResponse([
            'error' => 'invalid_grant',
            'error_description' => 'The token was not issued to this client.',
            'message' => 'The token was not issued to this client.',
        ], 400);
    }

    /**
     * D5: a completed revocation is a chained authorization event
     * (decision allow — the action executed; reason carries the
     * trigger). Fail-closed: an unauditable revocation throws rather
     * than succeeding silently.
     */
    private function recordRevocation(string $userId, string $clientId, string $jti): void
    {
        app(\App\Platform\Identity\Federation\AuthEventRecorder::class)->record([
            'event_type' => 'token.revoked',
            'decision' => 'allow',
            'subject_id' => $userId !== '' ? $userId : null,
            'actor_client_id' => $clientId,
            'token_jti' => $jti,
            'reason_code' => 'token_revoked',
        ]);
    }
}
