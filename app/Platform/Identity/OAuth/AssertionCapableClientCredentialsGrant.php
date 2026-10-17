<?php

namespace App\Platform\Identity\OAuth;

use DateInterval;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Client-credentials grant with RFC 7523 client authentication
 * (ADR-0020 D3): machine clients (BFFs, services) authenticate with a
 * signed assertion instead of a shared secret — no constructor or
 * behavior changes beyond the shared trait's validateClient branch.
 *
 * 9b (ADR-0022): also carries the A5 per-profile allowed_scopes
 * ceiling onto the machine side of the token endpoint. The registry
 * (league's validateScopes) answers "is this scope registered at
 * all"; the ceiling answers "may THIS client ask for it" — a
 * registered scope is not an entitlement, and a profile with no
 * allowed_scopes may mint only scope-less tokens.
 */
final class AssertionCapableClientCredentialsGrant extends ClientCredentialsGrant
{
    use AuthenticatesClientAssertions;
    use EnforcesProfileScopes;

    /**
     * Fork of league 9.4.1 ClientCredentialsGrant::
     * respondToAccessTokenRequest — body-for-body EXCEPT the A5/9b
     * ceiling between validateScopes and finalizeScopes (the same
     * position the auth-code token seam uses). finalizeScopes only
     * ever filters, never adds, so nothing can materialize a scope
     * between the ceiling and the persisted token.
     *
     * No redirect context exists at the token endpoint, so the deny
     * is league's 400 invalid_scope JSON ({error, error_description,
     * message}) — identical shape to an unregistered-scope deny here.
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $client = $this->validateClient($request);

        if (! $client->isConfidential()) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidClient($request);
        }

        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));

        // 9b ceiling: fail closed against the profile's allowed_scopes.
        // An empty request (the machine norm) passes untouched.
        $this->enforceProfileScopes(
            array_map(
                static fn ($scope): string => $scope->getIdentifier(),
                $scopes
            ),
            $client->getIdentifier()
        );

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client);

        // Issue and persist access token
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, null, $finalizedScopes);

        // Send event to emitter
        $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));

        // Inject access token into response type
        $responseType->setAccessToken($accessToken);

        return $responseType;
    }
}
