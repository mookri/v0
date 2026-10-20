<?php

namespace App\Platform\Identity\OAuth;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;

/**
 * RFC 8693 §2.2 token response: league's bearer response plus the
 * REQUIRED `issued_token_type`. Built only by TokenExchangeGrant (the
 * grant returns its own response instance — league serializes whatever
 * the grant hands back), so the extra field can never leak onto RFC
 * 6749 flows, and — since league only includes `refresh_token` when one
 * was set — an exchanged response carries no refresh lineage (D4).
 */
final class ExchangedTokenResponse extends BearerTokenResponse
{
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        return ['issued_token_type' => TokenExchangeGrant::TOKEN_TYPE];
    }
}
