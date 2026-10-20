<?php

namespace App\Platform\Identity\OAuth;

use League\OAuth2\Server\Grant\AuthCodeGrant;

/**
 * Authorization-code grant with RFC 7523 client authentication
 * (ADR-0020 D3). Construction mirrors Passport's `buildAuthCodeGrant`
 * exactly (auth-code repo, refresh repo, 10 min code TTL); the server
 * re-applies the rest of its wiring when the grant is enabled — keep
 * the constructor in sync with Passport's factory.
 *
 * Not `final`: 9a (ADR-0021) layers OidcAuthCodeGrant on top (the OIDC
 * deltas — mandatory nonce, ceremony facts, ID token minting) without
 * re-implementing the assertion branch.
 */
class AssertionCapableAuthCodeGrant extends AuthCodeGrant
{
    use AuthenticatesClientAssertions;
}
