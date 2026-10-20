<?php

namespace App\Platform\Identity\OAuth;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestEvent;
use Psr\Http\Message\ServerRequestInterface;

/**
 * RFC 7523 `private_key_jwt` client authentication (ADR-0020 D3).
 *
 * The assertion branch must sit INSIDE validateClient: league only
 * consults the client repository for confidential clients, and only
 * with a non-empty shared secret — an assertion-only request would be
 * rejected as a missing `client_secret` before any repository saw it.
 * Shared here by every client-authenticated grant (authorization_code,
 * refresh_token, client_credentials) so all three authenticate
 * identically; without an assertion the method defers to league
 * unchanged.
 *
 * Fail-closed rules (never downgrade, never widen):
 *  - An assertion, once presented, is the ONLY accepted credential for
 *    that request: wrong type or failed verification fails the
 *    exchange — a valid shared secret alongside a bad assertion does
 *    not rescue it.
 *  - `client_id` remains mandatory in the body and must equal the
 *    assertion's iss/sub (RFC 7523 permits omitting it; this
 *    deployment requires it — profiles, CheckOAuthGrant and league all
 *    key off it).
 *  - Failures emit league's CLIENT_AUTHENTICATION_FAILED and return
 *    the protocol's invalid_client (401), same as a wrong secret.
 */
trait AuthenticatesClientAssertions
{
    protected function validateClient(ServerRequestInterface $request): ClientEntityInterface
    {
        $body = (array) $request->getParsedBody();
        $assertion = is_string($body['client_assertion'] ?? null) ? trim($body['client_assertion']) : '';

        if ($assertion === '') {
            return parent::validateClient($request);
        }

        $type = is_string($body['client_assertion_type'] ?? null) ? trim($body['client_assertion_type']) : '';

        if ($type !== ClientAssertionVerifier::ASSERTION_TYPE) {
            $this->rejectClientAssertion($request);
        }

        [$clientId] = $this->getClientCredentials($request);

        $client = $this->getClientEntityOrFail($clientId, $request);

        if (! app(ClientAssertionVerifier::class)->verify($assertion, $clientId)) {
            $this->rejectClientAssertion($request);
        }

        return $client;
    }

    private function rejectClientAssertion(ServerRequestInterface $request): never
    {
        $this->getEmitter()->emit(new RequestEvent(
            RequestEvent::CLIENT_AUTHENTICATION_FAILED,
            $request
        ));

        throw OAuthServerException::invalidClient($request);
    }
}
