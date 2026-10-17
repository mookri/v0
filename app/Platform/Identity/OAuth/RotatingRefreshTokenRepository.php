<?php

namespace App\Platform\Identity\OAuth;

use Laravel\Passport\Bridge\RefreshTokenRepository as PassportRefreshTokenRepository;
use Laravel\Passport\Passport;

/**
 * Refresh rotation with reuse detection (SEC-003 §3.2): league rotates
 * (revokes the presented refresh token) by default; this subclass adds
 * the missing half — presenting an already-revoked refresh token kills
 * the entire lineage (all access + refresh tokens for the token's
 * user+client pair) and emits the security event. The grant then fails
 * closed with invalid_grant like any other revoked presentation.
 */
final class RotatingRefreshTokenRepository extends PassportRefreshTokenRepository
{
    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $token = Passport::refreshToken()->newQuery()->whereKey($tokenId)->first();

        if ($token === null) {
            return true;
        }

        if ((bool) $token->revoked) {
            $this->revokeLineage($token);

            return true;
        }

        // Suspended/deactivated owners fail closed at refresh time (the
        // access-token half is covered by the dual-accept status gate;
        // together no suspended credential survives past 15 minutes).
        return ! $this->ownerIsActive($token);
    }

    private function revokeLineage(mixed $token): void
    {
        $accessToken = Passport::token()->newQuery()->whereKey($token->access_token_id)->first();

        if ($accessToken === null) {
            return;
        }

        $userId = $accessToken->getAttribute('user_id');
        $clientId = $accessToken->getAttribute('client_id');

        $accessIds = Passport::token()->newQuery()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->pluck('id');

        Passport::token()->newQuery()->whereIn('id', $accessIds)->update(['revoked' => true]);
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $accessIds)->update(['revoked' => true]);

        // D2: reuse-detection kills propagate to the status cache at once.
        app(\App\Platform\Identity\Federation\RevocationStatusCache::class)
            ->invalidateTokens($accessIds->all());

        event(new TokenReuseDetected((string) $userId, (string) $clientId));
    }

    /**
     * Owner lifecycle gate: refresh tokens of suspended/deactivated
     * users read as revoked (fail closed without touching rows — the
     * rows die on their own expiry/rotation; suspension cascade wipes
     * them eagerly via the admin endpoint).
     */
    private function ownerIsActive(mixed $token): bool
    {
        $accessToken = Passport::token()->newQuery()->whereKey($token->access_token_id)->first();

        if ($accessToken === null) {
            return false;
        }

        $user = \App\Models\User::query()->find($accessToken->getAttribute('user_id'));

        return $user !== null && $user->isActive();
    }
}
