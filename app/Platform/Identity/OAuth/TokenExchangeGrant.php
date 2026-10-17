<?php

namespace App\Platform\Identity\OAuth;

use App\Models\User;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\PassportJwtVerifier;
use DateInterval;
use DateTimeInterface;
use Laravel\Passport\Client as PassportClient;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * RFC 8693 token exchange (ADR-0020 P6) — the cross-client identity
 * hop at the BFF boundary.
 *
 * A registered exchange client (confidential, first-party service,
 * grant allowed on BOTH the client row and its profile — CheckOAuthGrant
 * + league's supportsGrantType) authenticates and presents a subject
 * token (optionally its own actor token); it receives a NEW access
 * token bound to ITSELF carrying the same human subject.
 *
 * Binding security rules, each enforced here and tested:
 *  - Sole issuer (D1): subject and actor tokens must verify RS256
 *    against OUR public key (PassportJwtVerifier) and resolve to live
 *    rows — a foreign signer's token is worthless, no second signer
 *    exists anywhere in the path.
 *  - Clients request, never choose (D1): league signs `aud` from the
 *    authenticated client itself; an `audience`/`resource` parameter is
 *    accepted only when it matches a value registered to THIS client
 *    (client id or its profile audience label) — anything else is
 *    RFC 8693 `invalid_target`. The audience label never enters claims.
 *  - Subject identity comes from ROWS, not claims: the row's user_id is
 *    the authority (sub could collide with another table's numeric id),
 *    cross-checked against the verified claim, and the user must be
 *    active. Machine tokens (user_id null) carry no human identity to
 *    delegate — they are refused, not repurposed.
 *  - Scopes narrow only: requested ⊆ subject, then the normal registry
 *    and client filters (finalizeScopes) — an exchange can never widen.
 *  - Actor token (optional) must be the presenting client's OWN live
 *    credential — one client can never stand inside another's machine
 *    context (D1/D3 blast radius).
 *  - Revocation is authoritative (D2): subject/actor rows and the
 *    issuing client are read live here — no freshness cache excuses a
 *    revoked source at a delegation boundary.
 *  - No new refresh lineage (D4): the response carries an access token
 *    only; short-lived, re-exchangeable, rotation families stay where
 *    they started.
 *  - Every authorization outcome (allow and each deny reason) lands on
 *    the hash-chained auth_events chain (D5), correlated to the
 *    token.issued row through the shared jti reference. Malformed
 *    request shapes stay pure protocol errors (league) — malformed is
 *    not an authorization decision.
 */
final class TokenExchangeGrant extends AbstractGrant
{
    /** D3: private_key_jwt parity with every other client-authenticated grant. */
    use AuthenticatesClientAssertions;

    /** 9b (ADR-0022): the shared A5 allowed_scopes ceiling. */
    use EnforcesProfileScopes;

    /** Shared jti/row resolution with introspection and revocation. */
    use ResolvesOAuthTokens;

    public const GRANT_IDENTIFIER = 'urn:ietf:params:oauth:grant-type:token-exchange';

    public const TOKEN_TYPE = 'urn:ietf:params:oauth:token-type:access_token';

    /**
     * Per-request audit context. The grant instance survives across
     * requests inside one process (a reused container in tests), so the
     * state is cleared at the top of every response — a denied attempt
     * must never inherit the previous request's client identity.
     */
    private ?string $presentingClientId = null;

    private ?string $targetAudience = null;

    public function __construct(private readonly AuthEventRecorder $audit)
    {
    }

    public function getIdentifier(): string
    {
        return self::GRANT_IDENTIFIER;
    }

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $this->presentingClientId = null;
        $this->targetAudience = null;

        // Client authentication first (secret or assertion): an exchange
        // is a machine-boundary operation, never anonymous.
        $client = $this->validateClient($request);
        $clientId = (string) $client->getIdentifier();
        $this->presentingClientId = $clientId;
        $this->targetAudience = $clientId;

        if (! $client->isConfidential()) {
            throw $this->deny(
                'client_not_confidential',
                OAuthServerException::invalidClient($request)
            );
        }

        $profile = OAuthClientProfile::query()->where('client_id', $clientId)->first();

        // Exchange clients are first-party services only (parity with
        // the introspection carve-out): web, partner and pos profiles
        // never inherit a human subject across tokens (D1/D3).
        if ($profile === null || $profile->client_type !== OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE) {
            throw $this->deny(
                'client_not_exchange_eligible',
                OAuthServerException::unauthorizedClient('Token exchange is restricted to first-party service clients.')
            );
        }

        if ($profile->audience !== null && $profile->audience !== '') {
            $this->targetAudience = $profile->audience;
        }

        // ---- RFC 8693 request shape (protocol surface, not audited:
        //      malformed requests are not authorization decisions) ----

        $subjectToken = $this->getRequestParameter('subject_token', $request);
        if ($subjectToken === null) {
            throw OAuthServerException::invalidRequest('subject_token');
        }

        if ($this->getRequestParameter('subject_token_type', $request) !== self::TOKEN_TYPE) {
            throw OAuthServerException::invalidRequest('subject_token_type', 'Only access_token subject tokens are supported.');
        }

        $requestedType = $this->getRequestParameter('requested_token_type', $request);
        if ($requestedType !== null && $requestedType !== self::TOKEN_TYPE) {
            throw OAuthServerException::invalidRequest('requested_token_type', 'Only access_token issuance is supported.');
        }

        $actorToken = $this->getRequestParameter('actor_token', $request);
        if ($actorToken !== null && $this->getRequestParameter('actor_token_type', $request) !== self::TOKEN_TYPE) {
            throw OAuthServerException::invalidRequest('actor_token_type', 'Only access_token actor tokens are supported.');
        }

        // ---- Subject: verified against the sole issuer (D1) ----------

        $subject = PassportJwtVerifier::verify($subjectToken, time());
        if ($subject === null) {
            throw $this->deny(
                'subject_token_unverifiable',
                OAuthServerException::invalidGrant('The subject token could not be verified.')
            );
        }

        $subjectRow = $this->findAccessToken($subjectToken);
        if ($subjectRow === null
            || (bool) $subjectRow->getAttribute('revoked')
            || $this->expiredAt($subjectRow->getAttribute('expires_at'))) {
            throw $this->deny(
                'subject_token_inactive',
                OAuthServerException::invalidGrant('The subject token is not active.')
            );
        }

        // Row ↔ claim binding: the verified audience must be the token's
        // own client row (defends against a re-signed/grafted claim set).
        if ((string) $subjectRow->getAttribute('client_id') !== $subject['aud']) {
            throw $this->deny(
                'subject_token_binding_mismatch',
                OAuthServerException::invalidGrant('The subject token is inconsistent.')
            );
        }

        $sourceAudience = $this->audienceLabelFor((string) $subjectRow->getAttribute('client_id'));

        // A revoked issuing client is not a delegation source (D2/D3).
        $issuing = PassportClient::query()->find($subjectRow->getAttribute('client_id'));
        if ($issuing === null || (bool) $issuing->getAttribute('revoked')) {
            throw $this->deny(
                'subject_client_revoked',
                OAuthServerException::invalidGrant('The subject token is not active.'),
                $sourceAudience
            );
        }

        // Machine subject: the row has no user — there is no human
        // identity to delegate, and sub here is a CLIENT id that could
        // collide with another table's numeric ids. Refused, never
        // repurposed.
        if ($subjectRow->getAttribute('user_id') === null) {
            throw $this->deny(
                'subject_not_user_context',
                OAuthServerException::invalidGrant('The subject token carries no user context.'),
                $sourceAudience
            );
        }

        if ($subject['sub'] !== (string) $subjectRow->getAttribute('user_id')) {
            throw $this->deny(
                'subject_token_binding_mismatch',
                OAuthServerException::invalidGrant('The subject token is inconsistent.'),
                $sourceAudience
            );
        }

        $user = User::query()->find((string) $subjectRow->getAttribute('user_id'));
        if ($user === null || ! $user->isActive()) {
            throw $this->deny(
                'subject_inactive_user',
                OAuthServerException::invalidGrant('The subject token is not active.'),
                $sourceAudience
            );
        }

        $subjectId = (string) $user->getAuthIdentifier();

        // ---- Audience: registered values only (D1) -------------------

        $audience = $this->getRequestParameter('audience', $request);
        $resource = $this->getRequestParameter('resource', $request);

        if ($audience !== null && $resource !== null) {
            throw OAuthServerException::invalidRequest('audience', 'Pass either audience or resource, not both.');
        }

        $requestedAudience = $audience ?? $resource;
        if ($requestedAudience !== null
            && ! in_array($requestedAudience, $this->registeredAudiences($clientId, $profile), true)) {
            throw $this->deny(
                'audience_not_registered',
                new OAuthServerException(
                    'The requested audience is not registered for this client.',
                    0,
                    'invalid_target',
                    400
                ),
                $sourceAudience,
                $subjectId
            );
        }

        // ---- Actor token (optional): the presenter's OWN live credential ----

        if ($actorToken !== null) {
            $actor = PassportJwtVerifier::verify($actorToken, time());
            $actorRow = $actor === null ? null : $this->findAccessToken($actorToken);

            if ($actor === null
                || $actorRow === null
                || (bool) $actorRow->getAttribute('revoked')
                || $this->expiredAt($actorRow->getAttribute('expires_at'))
                || (string) $actorRow->getAttribute('client_id') !== $actor['aud']) {
                throw $this->deny(
                    'actor_token_invalid',
                    OAuthServerException::invalidGrant('The actor token is not active.'),
                    $sourceAudience,
                    $subjectId
                );
            }

            // The actor must be the authenticated presenting client's own
            // bound credential — a client can never stand inside another
            // client's machine context (D1/D3).
            if ($actor['aud'] !== $clientId) {
                throw $this->deny(
                    'actor_token_foreign_client',
                    OAuthServerException::invalidGrant('The actor token belongs to a different client.'),
                    $sourceAudience,
                    $subjectId
                );
            }
        }

        // ---- Scope: narrow only — an exchange can never widen (D1) ---

        $subjectScopes = $subject['scopes'];
        $scopeParam = $this->getRequestParameter('scope', $request);

        if ($scopeParam === null) {
            $requestedIds = $subjectScopes;
        } else {
            $requestedIds = array_values(array_filter(
                explode(' ', trim($scopeParam)),
                static fn (string $scope): bool => $scope !== ''
            ));

            // Subset check runs BEFORE the system registry so the
            // exchange-specific widening attempt is what lands on the
            // chain (the registry path is shared with every grant).
            $excess = array_diff($requestedIds, $subjectScopes);
            if ($excess !== []) {
                throw $this->deny(
                    'scope_not_subset',
                    OAuthServerException::invalidScope((string) reset($excess)),
                    $sourceAudience,
                    $subjectId
                );
            }
        }

        $scopes = $this->validateScopes($requestedIds);

        // 9b ceiling (ADR-0022): the subset rule above only compares
        // to the SUBJECT token's scopes — it cannot see that THIS
        // client's profile may not allow them. The ceiling binds the
        // PRESENTING client (never the subject's): a subject carrying
        // 'read' cannot hand it to a BFF whose allowed_scopes excludes
        // it. Fail closed; audited (D5) because an exchange scope deny
        // is an authorization outcome on the chain, unlike the plain
        // client_credentials/refresh denials (protocol surface only).
        $overCeiling = $this->disallowedScopesForClient($requestedIds, $clientId);
        if ($overCeiling !== []) {
            throw $this->deny(
                'scope_not_permitted',
                OAuthServerException::invalidScope((string) reset($overCeiling)),
                $sourceAudience,
                $subjectId
            );
        }

        $finalScopes = $this->scopeRepository->finalizeScopes($scopes, self::GRANT_IDENTIFIER, $client);

        // ---- Issue: claims are server-side (league binds aud to the
        //      authenticated client — the requester never picks) -------

        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $subjectId, $finalScopes);

        $this->getEmitter()->emit(new RequestAccessTokenEvent(
            RequestEvent::ACCESS_TOKEN_ISSUED,
            $request,
            $accessToken
        ));

        $this->audit->record([
            'event_type' => 'token.exchanged',
            'decision' => 'allow',
            'subject_id' => $subjectId,
            'token_jti' => $accessToken->getIdentifier(),
            'actor_client_id' => $clientId,
            'grant_type' => self::GRANT_IDENTIFIER,
            'source_audience' => $sourceAudience,
            'target_audience' => $this->targetAudience,
            'requested_scopes' => array_values($requestedIds),
            'granted_scopes' => array_map(
                static fn ($scope): string => $scope->getIdentifier(),
                $finalScopes
            ),
            'reason_code' => 'subject_verified',
        ]);

        // RFC 8693 §2.2 response: bearer access token + the REQUIRED
        // issued_token_type. No refresh token crosses the exchange
        // boundary (D4: rotation families stay where they started).
        $response = new ExchangedTokenResponse();
        $response->setAccessToken($accessToken);
        $response->setPrivateKey($this->privateKey);
        $response->setEncryptionKey($this->encryptionKey);

        return $response;
    }

    /**
     * Hash-chained deny record (D5), returning the league exception the
     * caller throws — one place, so no authorization outcome can skip
     * the chain. Raw ids go in as live values; the recorder stores only
     * HMAC-pseudonymous refs.
     */
    private function deny(
        string $reasonCode,
        OAuthServerException $exception,
        ?string $sourceAudience = null,
        ?string $subjectId = null
    ): OAuthServerException {
        $this->audit->record([
            'event_type' => 'token.exchanged',
            'decision' => 'deny',
            'subject_id' => $subjectId,
            'actor_client_id' => $this->presentingClientId,
            'grant_type' => self::GRANT_IDENTIFIER,
            'source_audience' => $sourceAudience,
            'target_audience' => $this->targetAudience,
            'reason_code' => $reasonCode,
        ]);

        return $exception;
    }

    /**
     * The values this client may ask the exchange for: its own identity
     * (client id) and its registered profile audience label. Anything
     * else would select an unregistered audience (D1).
     *
     * @return list<string>
     */
    private function registeredAudiences(string $clientId, OAuthClientProfile $profile): array
    {
        $audiences = [$clientId];

        if ($profile->audience !== null && $profile->audience !== '') {
            $audiences[] = $profile->audience;
        }

        return $audiences;
    }

    /**
     * Audit-facing audience label of an arbitrary client: its profile
     * label when registered, else the client id itself. Labels keep the
     * chain readable (source/target read as surfaces, not raw ids);
     * claims are unaffected — they always carry client ids.
     */
    private function audienceLabelFor(string $clientId): string
    {
        $profile = OAuthClientProfile::query()->where('client_id', $clientId)->first();

        if ($profile !== null && $profile->audience !== null && $profile->audience !== '') {
            return $profile->audience;
        }

        return $clientId;
    }

    private function expiredAt(mixed $at): bool
    {
        if ($at === null) {
            return false;
        }

        if ($at instanceof DateTimeInterface) {
            return $at->getTimestamp() <= time();
        }

        if (is_numeric($at)) {
            return (int) $at <= time();
        }

        $timestamp = strtotime((string) $at);

        return $timestamp !== false && $timestamp <= time();
    }
}
