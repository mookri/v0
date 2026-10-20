<?php

namespace App\Platform\Identity\OAuth;

use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

/**
 * A-SEC-004-6: the access-token row carries the OP session id when the
 * grant stamped one onto the entity (session-bound mints only — a
 * machine token keeps a NULL sid and never dies with a browser
 * session). The write lives HERE, inside the single persist INSERT, so
 * the row's column and the JWT's `sid` claim always carry the same
 * value: the stamp travels on the entity from the grant, and neither
 * half can be written without the other.
 *
 * A-SEC-004-9 adds `dpop_jkt` to the same INSERT for the same reason:
 * the row column and the JWT `cnf.dpop_jkt` claim both come from the
 * entity stamp, so the binding a resource server enforces and the
 * binding it reads out of the token can never disagree.
 *
 * Bound in AppServiceProvider over Passport's bridge repository (the
 * same container seam as RotatingRefreshTokenRepository).
 */
final class SessionBoundAccessTokenRepository extends AccessTokenRepository
{
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        Passport::token()->forceFill([
            'id' => $id = $accessTokenEntity->getIdentifier(),
            'user_id' => $userId = $accessTokenEntity->getUserIdentifier(),
            'client_id' => $clientId = $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => $accessTokenEntity->getScopes(),
            'sid' => $accessTokenEntity instanceof CustodyAccessToken
                ? $accessTokenEntity->sessionId()
                : null,
            'dpop_jkt' => $accessTokenEntity instanceof CustodyAccessToken
                ? $accessTokenEntity->dpopJkt()
                : null,
            'revoked' => false,
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
        ])->save();

        $this->events->dispatch(new AccessTokenCreated($id, $userId, $clientId));
    }
}
