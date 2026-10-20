<?php

namespace App\Platform\Identity\OAuth;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;

/**
 * OIDC token response (9a, ADR-0021): league's bearer response plus
 * the ID token minted by OidcAuthCodeGrant. Built only by that grant
 * (league serializes whatever the grant hands back), so the extra
 * field can never leak onto refresh / client-credentials / exchange
 * responses — an ID token travels only from an `openid` code exchange
 * at the token endpoint, never anywhere else.
 */
final class OidcTokenResponse extends BearerTokenResponse
{
    private ?string $idToken = null;

    public function withIdToken(string $idToken): self
    {
        $this->idToken = $idToken;

        return $this;
    }

    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        return $this->idToken === null ? [] : ['id_token' => $this->idToken];
    }
}
