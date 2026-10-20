<?php

namespace App\Platform\Identity\OAuth;

use App\Models\User;
use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\OAuth\Backchannel\SessionBindingRecorder;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use DateInterval;
use DateTimeImmutable;
use League\OAuth2\Server\CodeChallengeVerifiers\S256Verifier;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * OIDC authorization-code grant (9a, ADR-0021). Extends the
 * assertion-capable grant (RFC 7523 client auth is preserved — this
 * IS that grant, one level down) with the OIDC deltas:
 *
 *  - **Mandatory nonce (D3):** an `openid`-scoped authorization
 *    request without a nonce is rejected at the authorize endpoint
 *    (400 invalid_request, never a redirect — the flow cannot proceed
 *    to a replay-bindable code).
 *  - **Ceremony facts:** auth_time/amr/acr/sid are read from the
 *    federation login session at completion and minted into the
 *    encrypted code payload alongside the nonce, then into the ID
 *    token (they never ride the request object — Passport's consent
 *    round-trip unserializes that against a fixed class allowlist).
 *  - **ID token:** only from an `openid` code exchange at the token
 *    endpoint — RS256, kid = RFC 7638 thumbprint, iss from the shared
 *    IssuerIdentifier, sub = external_id ?? id (userinfo parity,
 *    documented divergence from the numeric access-token sub).
 *
 * Fork hazards (keep in sync with league 9.4.1 AuthCodeGrant — the
 * installed vendor copy is the source of truth): completeAuthorization-
 * Request, respondToAccessTokenRequest and their private helpers
 * (validateAuthorizationCode / validateCodeChallenge) are copied
 * body-for-body because league keeps them private and the auth-code
 * payload is built inside them. The repo's existing fork precedent is
 * RecoverableRefreshTokenGrant (refresh), pinned by tests.
 */
final class OidcAuthCodeGrant extends AssertionCapableAuthCodeGrant
{
    use EnforcesDpopProof;
    use EnforcesProfileScopes;

    /**
     * league keeps authCodeTTL private (constructor promotion) — the
     * fork needs it for the payload expiry, so construction mirrors
     * league's signature and keeps its own copy. Keep in sync.
     */
    private DateInterval $oidcCodeTTL;

    /**
     * Code-challenge verifiers, mirrored from league's own
     * constructor registration (league keeps its array private).
     *
     * @var array<string, \League\OAuth2\Server\CodeChallengeVerifiers\CodeChallengeVerifierInterface>
     */
    private array $oidcChallengeVerifiers = [];

    public function __construct(
        AuthCodeRepositoryInterface $authCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        DateInterval $authCodeTTL
    ) {
        parent::__construct($authCodeRepository, $refreshTokenRepository, $authCodeTTL);

        $this->oidcCodeTTL = $authCodeTTL;

        if (in_array('sha256', hash_algos(), true)) {
            $s256 = new S256Verifier();
            $this->oidcChallengeVerifiers[$s256->getMethod()] = $s256;
        }

        // Only S256 is registered. `plain` is deliberately absent: the
        // challenge would then be the verifier itself, so anyone who
        // observes the authorization request URL (referrer, proxy log,
        // browser history) could redeem the code. The discovery document
        // advertises S256 only, so accepting `plain` was also a spec
        // violation.
    }

    /**
     * Deliberately the BASE AuthorizationRequest (not a subclass):
     * Passport 13.8 serializes the auth request into the consent
     * session and unserializes it against a fixed `allowed_classes`
     * allowlist that names only the base class + Bridge Client/Scope/
     * User (see RetrievesAuthRequestFromSession). A subclass would
     * degrade to __PHP_Incomplete_Class across the consent POST. The
     * OIDC facts (nonce, ceremony) therefore never ride the request
     * object — they are read from the query + session directly in
     * completeAuthorizationRequest and minted into the encrypted code
     * payload, which never crosses Passport's allowlist.
     */
    protected function createAuthorizationRequest(): AuthorizationRequestInterface
    {
        return parent::createAuthorizationRequest();
    }

    /**
     * D3: the openid scope makes a nonce mandatory — validated
     * exactly (non-empty, bounded), and the ceremony facts for the
     * eventual ID token are captured here, at the moment the
     * authorization request is validated.
     */
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface
    {
        $authRequest = parent::validateAuthorizationRequest($request);

        // The per-client PKCE mandate is enforced HERE, at the authorize
        // endpoint. It used to be checked only on the PAR push path, so
        // a confidential client whose profile mandates PKCE could still
        // complete a full authorization-code flow with no challenge at
        // all (league only requires a challenge for *public* clients).
        // The authorization code is a bearer credential: without PKCE,
        // whoever observes the 302 can redeem it.
        $this->enforcePkceProfile($authRequest);

        $scopeIds = array_map(
            static fn ($scope): string => $scope->getIdentifier(),
            $authRequest->getScopes()
        );

        // A5: the per-profile allowed_scopes ceiling, enforced after
        // league's scope-registry validation (an unregistered scope
        // still dies first, exactly as league does it). The
        // redirect_uri has already been verified by the time this
        // runs, so the failure rides league's own invalid_scope
        // redirect — state intact — and the outer AppendsIssuer
        // middleware appends `iss` per D2. A profile without the
        // scope (or no profile at all) means the scope is not
        // allowed: fail closed.
        $this->enforceProfileScopes(
            $scopeIds,
            $authRequest->getClient()->getIdentifier(),
            $this->makeRedirectUri(
                $authRequest->getRedirectUri()
                    ?? $this->getClientRedirectUri($authRequest->getClient()),
                $authRequest->getState() !== null
                    ? ['state' => $authRequest->getState()]
                    : []
            )
        );

        if (! in_array('openid', $scopeIds, true)) {
            return $authRequest;
        }

        $nonce = trim((string) ($request->getQueryParams()['nonce'] ?? ''));

        if ($nonce === '') {
            throw OAuthServerException::invalidRequest(
                'nonce',
                'A nonce is required when the openid scope is requested.'
            );
        }

        if (strlen($nonce) > 512) {
            throw OAuthServerException::invalidRequest(
                'nonce',
                'The nonce must be at most 512 characters.'
            );
        }

        // The consent POST is a different HTTP request than this GET,
        // and Passport's session round-trip carries only the
        // serializable AuthorizationRequest (fixed class allowlist) —
        // so the validated nonce is stashed in the session here, keyed
        // per client, and pulled into the encrypted code payload at
        // completion. Login regenerations keep session data, so the
        // stash survives the federation login bounce.
        $laravelRequest = request();

        if ($laravelRequest->hasSession()) {
            $laravelRequest->session()->put(
                'oidc.pending_nonce.'.$authRequest->getClient()->getIdentifier(),
                $nonce
            );
        }

        return $authRequest;
    }

    /**
     * Enforce the client profile's PKCE mandate and the S256-only rule.
     *
     * Runs after league validated the redirect_uri, so the failure rides
     * league's verified redirect (RFC 6749 §4.1.2.1) instead of an
     * unverified target.
     */
    private function enforcePkceProfile(AuthorizationRequestInterface $authRequest): void
    {
        $clientId = $authRequest->getClient()->getIdentifier();
        $profile = OAuthClientProfile::query()->where('client_id', $clientId)->first();

        if ($profile === null) {
            // A client without a profile is rejected elsewhere (the
            // profile is mandatory); nothing to mandate here.
            return;
        }

        $challenge = $authRequest->getCodeChallenge();
        $method = $authRequest->getCodeChallengeMethod();

        if ($challenge !== null && $challenge !== '' && strtolower((string) $method) !== 's256') {
            throw OAuthServerException::invalidRequest(
                'code_challenge_method',
                'Only the S256 code challenge method is supported.'
            );
        }

        if ((bool) $profile->require_pkce && ($challenge === null || $challenge === '')) {
            throw OAuthServerException::invalidRequest(
                'code_challenge',
                'code_challenge is required for this client.'
            );
        }
    }

    /**
     * Fork of league 9.4.1 AuthCodeGrant::completeAuthorizationRequest,
     * body-for-body EXCEPT the OIDC block in the encrypted code
     * payload (league has no concept of one). Keep in sync: the
     * `$this->authCodeTTL` private became `$this->oidcCodeTTL`.
     */
    public function completeAuthorizationRequest(AuthorizationRequestInterface $authorizationRequest): ResponseTypeInterface
    {
        if ($authorizationRequest->getUser() instanceof UserEntityInterface === false) {
            throw new LogicException('An instance of UserEntityInterface should be set on the AuthorizationRequest');
        }

        $finalRedirectUri = $authorizationRequest->getRedirectUri()
                          ?? $this->getClientRedirectUri($authorizationRequest->getClient());

        // The user approved the client, redirect them back with an auth code
        if ($authorizationRequest->isAuthorizationApproved() === true) {
            $authCode = $this->issueAuthCode(
                $this->oidcCodeTTL,
                $authorizationRequest->getClient(),
                $authorizationRequest->getUser()->getIdentifier(),
                $authorizationRequest->getRedirectUri(),
                $authorizationRequest->getScopes()
            );

            $payload = [
                'client_id'             => $authCode->getClient()->getIdentifier(),
                'redirect_uri'          => $authCode->getRedirectUri(),
                'auth_code_id'          => $authCode->getIdentifier(),
                'scopes'                => $authCode->getScopes(),
                'user_id'               => $authCode->getUserIdentifier(),
                'expire_time'           => (new DateTimeImmutable())->add($this->oidcCodeTTL)->getTimestamp(),
                'code_challenge'        => $authorizationRequest->getCodeChallenge(),
                'code_challenge_method' => $authorizationRequest->getCodeChallengeMethod(),
            ];

            // 9a (ADR-0021): openid flows carry their replay-binding
            // nonce and ceremony facts inside the encrypted payload —
            // confidentiality and integrity come from league's
            // encryption, exactly like every other payload field. The
            // nonce was stashed in the session at validate time (this
            // consent POST carries no nonce of its own); a missing
            // stash cannot happen: validateAuthorizationRequest
            // rejects any openid request that lacks one, and this
            // completion only follows that validation in the same
            // session. Ceremony facts read the login session.
            if (in_array('openid', array_map(
                static fn ($scope): string => $scope->getIdentifier(),
                $authorizationRequest->getScopes()
            ), true)) {
                $ceremony = $this->ceremonyFacts();
                $clientId = $authorizationRequest->getClient()->getIdentifier();
                $laravelRequest = request();
                $stashed = $laravelRequest->hasSession()
                    ? $laravelRequest->session()->pull('oidc.pending_nonce.'.$clientId)
                    : null;

                // A-SEC-004-5: bind this OP session to the client —
                // the enumeration source used when the session ends to
                // find which RPs owe a back-channel logout token. Best
                // effort by design (never fails an authorization; the
                // recorder reports what it cannot write).
                SessionBindingRecorder::record(
                    $ceremony['sid'],
                    $clientId,
                    (int) $authCode->getUserIdentifier()
                );

                $payload['oidc'] = [
                    'nonce' => is_string($stashed) ? $stashed : '',
                    'auth_time' => $ceremony['auth_time'],
                    'amr' => $ceremony['amr'],
                    'acr' => $ceremony['acr'],
                    'sid' => $ceremony['sid'],
                ];
            }

            $jsonPayload = json_encode($payload);

            if ($jsonPayload === false) {
                throw new LogicException('An error was encountered when JSON encoding the authorization request response');
            }

            $response = new RedirectResponse();
            $response->setRedirectUri(
                $this->makeRedirectUri(
                    $finalRedirectUri,
                    [
                        'code'  => $this->encrypt($jsonPayload),
                        'state' => $authorizationRequest->getState(),
                    ]
                )
            );

            return $response;
        }

        // The user denied the client, redirect them back with an error
        throw OAuthServerException::accessDenied(
            'The user denied the request',
            $this->makeRedirectUri(
                $finalRedirectUri,
                [
                    'state' => $authorizationRequest->getState(),
                ]
            )
        );
    }

    /**
     * Fork of league 9.4.1 AuthCodeGrant::respondToAccessTokenRequest,
     * body-for-body EXCEPT: league's private validateAuthorizationCode
     * and validateCodeChallenge are inlined (both private in league),
     * and an `openid` code exchange returns an OidcTokenResponse
     * carrying the ID token minted from the payload's oidc block. A
     * non-openid exchange behaves exactly as league does. Keep in
     * sync.
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $client = $this->validateClient($request);

        // A-SEC-004-9 (RFC 9449): the DPoP proof is validated BEFORE
        // the code is touched — league revokes the code only at the end
        // of a successful run, so a mandate/proof denial here leaves the
        // authorization grant intact for a corrected retry with the same
        // PKCE verifier. Null = the unbound mint path (unchanged).
        $dpopJkt = $this->dpopJktForTokenRequest($request, $client->getIdentifier());

        $encryptedAuthCode = $this->getRequestParameter('code', $request);

        if ($encryptedAuthCode === null) {
            throw OAuthServerException::invalidRequest('code');
        }

        $sessionSid = null;

        try {
            $authCodePayload = json_decode($this->decrypt($encryptedAuthCode));

            $this->validateAuthorizationCode($authCodePayload, $client, $request);

            $validatedScopes = $this->validateScopes($authCodePayload->scopes);

            // A-SEC-004-6: the ceremony's OP-session sid rides the code
            // payload (stashed at consent) — stamped onto the access
            // token below so the session's token lineage can be
            // revoked as one at logout. Absent for pre-9a/test codes:
            // an unstamped token simply has no lineage to revoke.
            if (is_object($authCodePayload->oidc ?? null)
                && is_string($authCodePayload->oidc->sid ?? null)
                && $authCodePayload->oidc->sid !== '') {
                $sessionSid = (string) $authCodePayload->oidc->sid;
            }

            // A5 token seam: re-assert the per-profile allowed_scopes
            // ceiling after validateScopes, before finalizeScopes —
            // the profile may have been narrowed since the code was
            // minted (the payload alone never decides entitlement).
            // No redirect context exists at the token endpoint, so
            // league answers 400 invalid_scope JSON exactly as it does
            // for an unregistered scope here.
            $this->enforceProfileScopes(
                array_map(
                    static fn ($scope): string => $scope->getIdentifier(),
                    $validatedScopes
                ),
                $client->getIdentifier()
            );

            $scopes = $this->scopeRepository->finalizeScopes(
                $validatedScopes,
                $this->getIdentifier(),
                $client,
                $authCodePayload->user_id,
                $authCodePayload->auth_code_id
            );
        } catch (\InvalidArgumentException $e) {
            throw OAuthServerException::invalidGrant('Cannot validate the provided authorization code');
        } catch (LogicException $e) {
            throw OAuthServerException::invalidRequest('code', 'Issue decrypting the authorization code', $e);
        }

        $codeVerifier = $this->getRequestParameter('code_verifier', $request);

        // If a code challenge isn't present but a code verifier is, reject the request to block PKCE downgrade attack
        if (! isset($authCodePayload->code_challenge) && $codeVerifier !== null) {
            throw OAuthServerException::invalidRequest(
                'code_challenge',
                'code_verifier received when no code_challenge is present'
            );
        }

        if (isset($authCodePayload->code_challenge)) {
            $this->verifyCodeChallenge($authCodePayload, $codeVerifier);
        }

        // Issue and persist new access token
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $authCodePayload->user_id, $scopes, $sessionSid, $dpopJkt);
        $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));
        $responseType->setAccessToken($accessToken);

        // Issue and persist new refresh token if given
        $refreshToken = $this->issueRefreshToken($accessToken);

        if ($refreshToken !== null) {
            $this->getEmitter()->emit(new RequestRefreshTokenEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request, $refreshToken));
            $responseType->setRefreshToken($refreshToken);
        }

        // 9a (ADR-0021): an openid code's exchange is the ONLY place
        // an ID token is minted — never on refresh, client credentials
        // or exchange (their grants build their own responses).
        if (isset($authCodePayload->oidc) && is_object($authCodePayload->oidc)) {
            $oidcResponse = (new OidcTokenResponse())->withIdToken(
                $this->mintIdToken($authCodePayload, $client)
            );
            $oidcResponse->setAccessToken($accessToken);

            if ($refreshToken !== null) {
                $oidcResponse->setRefreshToken($refreshToken);
            }

            $oidcResponse->setPrivateKey($this->privateKey);
            $oidcResponse->setEncryptionKey($this->encryptionKey);

            $responseType = $oidcResponse;
        }

        // Revoke used auth code
        $this->authCodeRepository->revokeAuthCode($authCodePayload->auth_code_id);

        return $responseType;
    }

    /**
     * league 9.4.1 validateAuthorizationCode, inlined (private there).
     * Keep in sync.
     */
    private function validateAuthorizationCode(
        stdClass $authCodePayload,
        ClientEntityInterface $client,
        ServerRequestInterface $request
    ): void {
        if (! property_exists($authCodePayload, 'auth_code_id')) {
            throw OAuthServerException::invalidRequest('code', 'Authorization code malformed');
        }

        if (time() > $authCodePayload->expire_time) {
            throw OAuthServerException::invalidGrant('Authorization code has expired');
        }

        if ($this->authCodeRepository->isAuthCodeRevoked($authCodePayload->auth_code_id) === true) {
            throw OAuthServerException::invalidGrant('Authorization code has been revoked');
        }

        if ($authCodePayload->client_id !== $client->getIdentifier()) {
            throw OAuthServerException::invalidRequest('code', 'Authorization code was not issued to this client');
        }

        // The redirect URI is required in this request if it was specified
        // in the authorization request
        $redirectUri = $this->getRequestParameter('redirect_uri', $request);
        if ($authCodePayload->redirect_uri !== null && $redirectUri === null) {
            throw OAuthServerException::invalidRequest('redirect_uri');
        }

        // If a redirect URI has been provided ensure it matches the stored redirect URI
        if ($redirectUri !== null && $authCodePayload->redirect_uri !== $redirectUri) {
            throw OAuthServerException::invalidRequest('redirect_uri', 'Invalid redirect URI');
        }
    }

    /**
     * league 9.4.1 validateCodeChallenge, inlined (private there).
     * Keep in sync.
     */
    private function verifyCodeChallenge(stdClass $authCodePayload, ?string $codeVerifier): void
    {
        if ($codeVerifier === null) {
            throw OAuthServerException::invalidRequest('code_verifier');
        }

        // Validate code_verifier according to RFC-7636
        // @see: https://tools.ietf.org/html/rfc7636#section-4.1
        if (preg_match('/^[A-Za-z0-9-._~]{43,128}$/', $codeVerifier) !== 1) {
            throw OAuthServerException::invalidRequest(
                'code_verifier',
                'Code Verifier must follow the specifications of RFC-7636.'
            );
        }

        if (property_exists($authCodePayload, 'code_challenge_method')) {
            if (isset($this->oidcChallengeVerifiers[$authCodePayload->code_challenge_method])) {
                $codeChallengeVerifier = $this->oidcChallengeVerifiers[$authCodePayload->code_challenge_method];

                if (
                    ! property_exists($authCodePayload, 'code_challenge') ||
                    ! isset($authCodePayload->code_challenge) ||
                    $codeChallengeVerifier->verifyCodeChallenge($codeVerifier, $authCodePayload->code_challenge) === false
                ) {
                    throw OAuthServerException::invalidGrant('Failed to verify `code_verifier`.');
                }
            } else {
                throw OAuthServerException::invalidRequest(
                    'code_challenge_method',
                    sprintf(
                        'Unsupported code challenge method `%s`.',
                        $authCodePayload->code_challenge_method
                    )
                );
            }
        }
    }

    /**
     * Fork of league 9.4.1 AbstractGrant::issueAccessToken, body-for-
     * body EXCEPT the optional stamps (A-SEC-004-6 session sid,
     * A-SEC-004-9 DPoP jkt): each is stamped onto the entity BEFORE
     * persist, so the token ROW (SessionBoundAccessTokenRepository)
     * and the minted JWT claim (CustodyAccessToken `sid` / `cnf`) each
     * carry the same value from one source. The extra parameters are
     * opt-in — every other league caller passes nothing and gets the
     * stock body. Keep in sync.
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
     * RS256 ID token mint. Claims: iss (shared derivation, byte-equal
     * to discovery and the RFC 9207 iss parameter), sub = external_id
     * ?? id (must equal the userinfo sub per OIDC Core §2 — a
     * documented divergence from the numeric access-token sub), aud =
     * the code's client, exp 10 min, plus the ceremony facts. Header
     * carries the RFC 7638 thumbprint kid so BFF JWKS consumers can
     * cache and detect rotation.
     */
    private function mintIdToken(stdClass $payload, ClientEntityInterface $client): string
    {
        $oidc = $payload->oidc;
        $now = new DateTimeImmutable();

        $user = User::query()->find($payload->user_id);
        $sub = $user !== null && $user->external_id !== null && $user->external_id !== ''
            ? (string) $user->external_id
            : (string) $payload->user_id;

        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates()))
            ->issuedBy(IssuerIdentifier::value())
            ->relatedTo($sub)
            ->permittedFor((string) $client->getIdentifier())
            ->issuedAt($now)
            ->expiresAt($now->add(new DateInterval('PT10M')))
            ->withClaim('auth_time', (int) ($oidc->auth_time ?? $now->getTimestamp()))
            ->withClaim('nonce', (string) $oidc->nonce);

        if (is_string($oidc->sid ?? null) && $oidc->sid !== '') {
            $builder = $builder->withClaim('sid', $oidc->sid);
        }

        if (is_array($oidc->amr ?? null) && $oidc->amr !== []) {
            $builder = $builder->withClaim('amr', array_values($oidc->amr));
        }

        if (is_string($oidc->acr ?? null) && $oidc->acr !== '') {
            $builder = $builder->withClaim('acr', $oidc->acr);
        }

        $kid = self::signingKid();

        if ($kid !== null) {
            $builder = $builder->withHeader('kid', $kid);
        }

        // The signer comes from the signing custody: stock RS256 with
        // this grant's key contents under file custody (byte-exact
        // pre-custody behavior), the port-backed RS256 signer under
        // KMS custody — the algorithm never changes for consumers, only
        // where the private half lives.
        return $builder
            ->getToken(SigningCustody::resolve()->jwtSigner(), InMemory::plainText($this->privateKey->getKeyContents()))
            ->toString();
    }

    /**
     * The published thumbprint of the CURRENT signing public half —
     * resolved through the signing custody. DiscoveryController's JWKS
     * source routes through the same port, so the kid and the published
     * key set are in sync BY CONSTRUCTION (the old per-call config/file
     * walk, which had to be kept in sync by hand, is gone). Null when
     * the custody has no readable public half: consumers fall back to
     * trying the small kid-labelled key set (league mints kid-less
     * headers anyway).
     */
    private static function signingKid(): ?string
    {
        try {
            $pem = SigningCustody::resolve()->publicKeyPem();
        } catch (FederationConfigurationException) {
            return null;
        }

        return Jwk::thumbprintFromPem($pem);
    }

    /**
     * Ceremony facts for the ID token: the facts stashed by the
     * federation login ceremony when present; a safe fallback
     * (auth_time = now, per-session sid, no amr/acr claims) for
     * sessions established without the ceremony (test harness,
     * pre-9a sessions). Never invents amr/acr — absent means absent.
     *
     * @return array{auth_time: int, amr: list<string>|null, acr: string|null, sid: string}
     */
    private function ceremonyFacts(): array
    {
        try {
            $store = app('session')->driver();
            $stored = $store->get('oidc.ceremony');

            if (is_array($stored) && isset($stored['auth_time'])) {
                $sid = is_string($stored['sid'] ?? null) && $stored['sid'] !== ''
                    ? $stored['sid']
                    : self::sidFor($store);

                return [
                    'auth_time' => (int) $stored['auth_time'],
                    'amr' => is_array($stored['amr'] ?? null) ? array_values($stored['amr']) : null,
                    'acr' => is_string($stored['acr'] ?? null) && $stored['acr'] !== '' ? $stored['acr'] : null,
                    'sid' => $sid,
                ];
            }

            return [
                'auth_time' => time(),
                'amr' => null,
                'acr' => null,
                'sid' => self::sidFor($store),
            ];
        } catch (\Throwable) {
            // No session store (CLI): one fresh sid per authorization.
            return [
                'auth_time' => time(),
                'amr' => null,
                'acr' => null,
                'sid' => (string) \Illuminate\Support\Str::ulid(),
            ];
        }
    }

    /**
     * Session-stable sid: consecutive authorizes from one browser
     * session share the sid (it identifies the OP session, not the
     * authorization request).
     */
    private static function sidFor(\Illuminate\Session\Store $store): string
    {
        $sid = $store->get('oidc.sid');

        if (! is_string($sid) || $sid === '') {
            $sid = (string) \Illuminate\Support\Str::ulid();
            $store->put('oidc.sid', $sid);
        }

        return $sid;
    }
}
