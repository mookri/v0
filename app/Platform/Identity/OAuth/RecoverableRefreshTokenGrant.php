<?php

namespace App\Platform\Identity\OAuth;

use DateInterval;
use DateTimeImmutable;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * D4 refresh contract (ADR-0020), wired over Passport's grant: league's
 * AuthorizationServer keys grants by identifier, so enabling this class
 * after Passport's registration replaces the built-in refresh grant in
 * place (same `refresh_token` identifier — discovery, client profiles
 * and CheckOAuthGrant are unaffected).
 *
 * Three changes over league:
 *
 * 1. Idempotent retry (lost-response recovery): a retry carrying a
 *    recorded Idempotency-Key with an identical request fingerprint,
 *    inside federation.refresh_retry_window_seconds, recovers the
 *    ORIGINAL response — but only after league client authentication
 *    succeeds, so recovered material never leaves without client
 *    credentials. Every other case (different key or fingerprint,
 *    elapsed window, store down) falls through to normal validation,
 *    where a consumed token is reuse -> lineage kill (unchanged).
 *
 * 2. Atomic rotation: league's expire-old + issue-successor sequence
 *    runs in ONE database transaction — either both happen or nothing
 *    changed, so a failure mid-rotation never leaves a consumed token
 *    without a successor (the presented token stays usable: recovery,
 *    not re-auth; no half-state). Validation — including reuse
 *    detection's lineage kill and its audit event — runs BEFORE the
 *    transaction, so those security writes persist regardless of the
 *    rotation outcome.
 *
 * 3. Session-lineage continuity (A-SEC-004-10): the successor of a
 *    session-bound family carries the SAME `sid` on claim and row as
 *    the original — rotation can never mint an access token (or its
 *    refresh token) that sits outside SessionTerminator's kill net.
 *
 * Plus D3: the shared AuthenticatesClientAssertions branch gives this
 * grant the same RFC 7523 `private_key_jwt` client authentication as
 * the other client-authenticated grants; plus A-SEC-004-9: the
 * EnforcesDpopProof trait keeps a bound family bound across this
 * grant's refresh (original-key proof or denial before any rotation
 * write).
 */
final class RecoverableRefreshTokenGrant extends RefreshTokenGrant
{
    use AuthenticatesClientAssertions;
    use EnforcesDpopProof;
    use EnforcesProfileScopes;

    public function __construct(
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly RefreshIdempotencyStore $store,
    ) {
        parent::__construct($refreshTokenRepository);
    }

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $idempotencyKey = trim($request->getHeaderLine('Idempotency-Key'));

        if ($idempotencyKey !== '') {
            $recorded = $this->store->recover($idempotencyKey, $this->fingerprintParams($request));

            if ($recorded !== null) {
                // Recovered material is released only to a client that
                // authenticates exactly as the original exchange did.
                $this->validateClient($request);

                return new ReplayResponseType($recorded);
            }
        }

        return $this->rotateAtomically($request, $responseType, $accessTokenTTL);
    }

    /**
     * League RefreshTokenGrant::respondToAccessTokenRequest, forked only
     * to put the expire-old + issue-successor half inside a single
     * transaction (D4 atomic rotation). Validation order is unchanged —
     * notably reuse detection still fires, and persists, before any
     * rotation write.
     */
    private function rotateAtomically(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        // Validate request
        $client = $this->validateClient($request);
        $oldRefreshToken = $this->validateOldRefreshToken($request, $client->getIdentifier());

        // A-SEC-004-6/9/10: the ORIGINAL token's row is the family's
        // lineage source. Its `dpop_jkt` keeps the family's DPoP
        // binding across refresh (RFC 9449 §5: present + prove the
        // ORIGINAL key — v1: no key rotation inside a family; an
        // unbound family may upgrade by presenting a proof) and its
        // `sid` carries the OP-session lineage onto the successor
        // (A-SEC-004-10), so rotation can never mint an access token
        // — or a refresh token hanging off it — that the session's
        // kill cannot see. Runs after the old-token validation so the
        // D4 reuse-detection position is untouched, and BEFORE any
        // rotation write so a denial leaves the family exactly as it
        // was.
        $original = Passport::token()->newQuery()
            ->where('id', $oldRefreshToken['access_token_id'])
            ->first();

        $oldJkt = (string) ($original !== null ? $original->getAttribute('dpop_jkt') : '');
        $rawSid = $original !== null ? $original->getAttribute('sid') : null;
        $sessionSid = is_string($rawSid) && $rawSid !== '' ? $rawSid : null;

        $dpopJkt = $this->dpopJktForTokenRequest($request, $client->getIdentifier());

        if ($oldJkt !== '' && $dpopJkt !== $oldJkt) {
            throw self::invalidDpopProof(
                $dpopJkt === null
                    ? 'The access token being refreshed is DPoP-bound and the request carried no DPoP proof.'
                    : 'The DPoP proof key does not match the binding of the access token being refreshed.'
            );
        }

        $scopes = $this->validateScopes(
            $this->getRequestParameter(
                'scope',
                $request,
                implode(self::SCOPE_DELIMITER_STRING, $oldRefreshToken['scopes'])
            )
        );

        // The OAuth spec says that a refreshed access token can have the original scopes or fewer so ensure
        // the request doesn't include any new scopes
        foreach ($scopes as $scope) {
            if (in_array($scope->getIdentifier(), $oldRefreshToken['scopes'], true) === false) {
                throw OAuthServerException::invalidScope($scope->getIdentifier());
            }
        }

        // 9b ceiling (ADR-0022): the subset rule above only compares
        // to the OLD token — it cannot see that the profile was
        // narrowed after that token was minted. Re-assert the A5
        // allowed_scopes ceiling against the (possibly narrowed) live
        // profile so refresh can never carry a revoked entitlement
        // forward. Empty scope set (the norm) passes untouched; deny
        // is league's 400 invalid_scope, no redirect context here.
        $this->enforceProfileScopes(
            array_map(
                static fn ($scope): string => $scope->getIdentifier(),
                $scopes
            ),
            $client->getIdentifier()
        );

        $userId = $oldRefreshToken['user_id'];
        if (is_int($userId)) {
            $userId = (string) $userId;
        }

        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $userId);

        \Laravel\Passport\Passport::token()->getConnection()->transaction(function () use (
            $request,
            $responseType,
            $accessTokenTTL,
            $oldRefreshToken,
            $client,
            $userId,
            $scopes,
            $sessionSid,
            $dpopJkt
        ): void {
            // Expire old tokens
            $this->accessTokenRepository->revokeAccessToken($oldRefreshToken['access_token_id']);
            if ($this->revokeRefreshTokens) {
                $this->refreshTokenRepository->revokeRefreshToken($oldRefreshToken['refresh_token_id']);
            }

            // Issue and persist new access token
            $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $userId, $scopes, $sessionSid, $dpopJkt);
            $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));
            $responseType->setAccessToken($accessToken);

            // Issue and persist new refresh token if given
            $refreshToken = $this->issueRefreshToken($accessToken);

            if ($refreshToken !== null) {
                $this->getEmitter()->emit(new RequestRefreshTokenEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request, $refreshToken));
                $responseType->setRefreshToken($refreshToken);
            }
        });

        return $responseType;
    }

    /**
     * Fork of league 9.4.1 AbstractGrant::issueAccessToken, body-for-
     * body EXCEPT the optional stamps: the DPoP jkt (A-SEC-004-9) and
     * the session sid (A-SEC-004-10 — carried forward from this
     * family's ORIGINAL token, so every rotated successor stays inside
     * SessionTerminator's `where sid =` kill net; a family that never
     * had a session keeps a NULL sid, faithful carry-forward, never
     * invented lineage). Each stamp lands on the entity BEFORE persist,
     * so the token ROW (SessionBoundAccessTokenRepository) and the
     * JWT claims (`sid` / `cnf.dpop_jkt`) carry the same value from
     * one source. The extra parameters are opt-in — league callers
     * pass nothing and get the stock body. Keep in sync with
     * OidcAuthCodeGrant::issueAccessToken.
     *
     * @param  \League\OAuth2\Server\Entities\ScopeEntityInterface[]  $scopes
     */
    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        ?string $userIdentifier,
        array $scopes = [],
        ?string $sessionSid = null,
        ?string $dpopJkt = null,
    ): AccessTokenEntityInterface {
        $maxGenerationAttempts = self::MAX_RANDOM_TOKEN_GENERATION_ATTEMPTS;

        $accessToken = $this->accessTokenRepository->getNewToken($client, $scopes, $userIdentifier);
        $accessToken->setExpiryDateTime((new DateTimeImmutable())->add($accessTokenTTL));
        $accessToken->setPrivateKey($this->privateKey);

        if ($accessToken instanceof CustodyAccessToken && is_string($sessionSid) && $sessionSid !== '') {
            $accessToken->withSessionId($sessionSid);
        }

        if ($accessToken instanceof CustodyAccessToken && is_string($dpopJkt) && $dpopJkt !== '') {
            $accessToken->withDpopJkt($dpopJkt);
        }

        while ($maxGenerationAttempts-- > 0) {
            $accessToken->setIdentifier($this->generateUniqueIdentifier());
            try {
                $this->accessTokenRepository->persistNewAccessToken($accessToken);

                return $accessToken;
            } catch (UniqueTokenIdentifierConstraintViolationException $e) {
                if ($maxGenerationAttempts === 0) {
                    throw $e;
                }
            }
        }

        // This should never be hit. It is here to work around a PHPStan false error
        return $accessToken;
    }

    /**
     * Mirror league's own credential extraction (getClientCredentials:
     * the body parameter wins, the HTTP Basic username is the fallback)
     * so the fingerprint an issuance records equals the one its retry
     * derives — on either request representation.
     *
     * @return array{client_id: string, refresh_token: string, scope: string}
     */
    private function fingerprintParams(ServerRequestInterface $request): array
    {
        [$basicUser] = $this->getBasicAuthCredentials($request);

        return [
            'client_id' => (string) ($this->getRequestParameter('client_id', $request, $basicUser) ?? ''),
            'refresh_token' => (string) ($this->getRequestParameter('refresh_token', $request) ?? ''),
            'scope' => (string) ($this->getRequestParameter('scope', $request) ?? ''),
        ];
    }
}
