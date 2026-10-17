<?php

namespace App\Platform\Identity\OAuth\Http\Controllers;

use App\Platform\Identity\OAuth\ClientAssertionVerifier;
use App\Platform\Identity\OAuth\EnforcesProfileScopes;
use App\Platform\Identity\OAuth\PushedAuthorizationRequest;
use App\Platform\Identity\OAuth\ResolvesOAuthTokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;

/**
 * RFC 9126 pushed-authorization endpoint: a confidential client
 * pre-validates its full authorization request over an authenticated
 * back channel and receives a single-use, short-TTL `request_uri` to
 * present at the browser-facing authorize endpoint. Validation here
 * mirrors the authorize endpoint one-for-one (response type, redirect
 * URI, PKCE S256, openid-nonce), so a pushed request replays without
 * surprises and an invalid one dies at the push — never in the
 * browser redirect (unverified redirect targets are never used for
 * errors).
 *
 * Client authentication mirrors the token endpoint posture
 * (client_secret_post or RFC 7523 private_key_jwt, fail-closed: an
 * assertion, once presented, is the only accepted credential).
 * Errors use league's shape ({error, error_description, message}) —
 * protocol surface, not the kernel envelope. Discovery deliberately
 * does NOT advertise a global PAR mandate (per-client only, D1).
 */
final class PushedAuthorizationRequestController
{
    use EnforcesProfileScopes;
    use ResolvesOAuthTokens;

    /**
     * The full authorization-request parameter set a push may carry.
     * Anything outside it is rejected — the middleware replays stored
     * params verbatim, so an unvalidated param would reach league's
     * authorize validation without ever having been checked here.
     *
     * @var list<string>
     */
    private const ALLOWED_PARAMS = [
        'response_type', 'client_id', 'redirect_uri', 'scope',
        'state', 'code_challenge', 'code_challenge_method', 'nonce',
    ];

    public function __construct(private readonly ClientAssertionVerifier $assertions)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        [$clientId, $denial] = $this->authenticateClient($request);

        if ($denial !== null) {
            return $denial;
        }

        // Optional params are nullable: ConvertEmptyStringsToNull turns
        // `scope=` (RFC 6749's "equivalent to omitting") into null, and
        // a null must fail neither the string rule nor the push.
        [$validated, $denial] = $this->validateProtocol($request, [
            'response_type' => ['required', 'string'],
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['sometimes', 'nullable', 'string'],
            'scope' => ['sometimes', 'nullable', 'string'],
            'state' => ['sometimes', 'nullable', 'string'],
            'code_challenge' => ['sometimes', 'nullable', 'string'],
            'code_challenge_method' => ['sometimes', 'nullable', 'string'],
            'nonce' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($denial !== null) {
            return $denial;
        }

        // A push must name the client it authenticated as — never a
        // third party's authorization request.
        if (! hash_equals($clientId, (string) $validated['client_id'])) {
            return self::error('invalid_request', 'client_id must match the authenticated client.', 400);
        }

        if ((string) $validated['response_type'] !== 'code') {
            return self::error('unsupported_response_type', 'Only the authorization code response type is supported.', 400);
        }

        $client = Client::query()->whereKey($clientId)->first();

        if ($client === null || (bool) $client->getAttribute('revoked')) {
            return self::error('invalid_client', 'Client authentication failed.', 401);
        }

        $redirectDenial = $this->validateRedirectUri($client, $validated);

        if ($redirectDenial !== null) {
            return $redirectDenial;
        }

        // Fill the single registered URI when the client omitted it
        // (league authorize semantics: exactly one registered URI
        // resolves an omitted redirect_uri).
        $redirectUris = array_values((array) $client->getAttribute('redirect_uris'));
        $redirectUri = (string) ($validated['redirect_uri'] ?? ($redirectUris[0] ?? ''));
        $validated['redirect_uri'] = $redirectUri;

        // A5: the per-profile allowed_scopes ceiling, mirrored here so
        // a scope the profile never allowed dies at the push (league-
        // shaped 400, back channel) — never later as a browser error
        // redirect. Same rule as the authorize and token seams
        // (EnforcesProfileScopes): no profile = nothing allowed, an
        // empty scope request always passes.
        $scopeDenial = $this->validateProfileScopes($clientId, $validated);

        if ($scopeDenial !== null) {
            return $scopeDenial;
        }

        $pkceDenial = $this->validatePkce($clientId, $validated);

        if ($pkceDenial !== null) {
            return $pkceDenial;
        }

        $nonceDenial = $this->validateNonce($validated);

        if ($nonceDenial !== null) {
            return $nonceDenial;
        }

        $unknown = array_diff(array_keys($request->all()), self::ALLOWED_PARAMS, ['client_assertion', 'client_assertion_type', 'client_secret']);

        if ($unknown !== []) {
            return self::error('invalid_request', 'Unsupported parameters: '.implode(', ', $unknown).'.', 400);
        }

        [$row, $requestUri] = PushedAuthorizationRequest::mint($clientId, $validated);

        return new JsonResponse([
            'request_uri' => $requestUri,
            'expires_in' => PushedAuthorizationRequest::TTL_SECONDS,
        ], 201);
    }

    /**
     * Confidential clients only, exactly the token endpoint's posture:
     * an assertion, once presented, is the ONLY accepted credential
     * (wrong type or failed verification fails the push — never a
     * downgrade to the shared secret).
     *
     * @param  array<string, mixed>  $validated
     */
    private function validateRedirectUri(Client $client, array $validated): ?JsonResponse
    {
        $registered = array_values((array) $client->getAttribute('redirect_uris'));

        if (! isset($validated['redirect_uri'])) {
            // League authorize accepts an omitted redirect_uri only
            // when exactly one URI is registered.
            return $registered === [] || count($registered) > 1
                ? self::error('invalid_request', 'redirect_uri is required.', 400)
                : null;
        }

        if (! in_array((string) $validated['redirect_uri'], $registered, true)) {
            return self::error('invalid_request', 'redirect_uri is not registered for this client.', 400);
        }

        return null;
    }

    /**
     * PKCE S256 is the only challenge method this deployment
     * advertises; clients whose profile requires PKCE must push a
     * challenge here (it replays at the authorize endpoint).
     *
     * @param  array<string, mixed>  $validated
     */
    private function validatePkce(string $clientId, array $validated): ?JsonResponse
    {
        $profile = \App\Platform\Identity\OAuth\OAuthClientProfile::query()
            ->where('client_id', $clientId)
            ->first();

        $challenge = (string) ($validated['code_challenge'] ?? '');
        $method = (string) ($validated['code_challenge_method'] ?? '');

        if ($challenge === '') {
            return $profile !== null && (bool) $profile->require_pkce
                ? self::error('invalid_request', 'code_challenge is required for this client.', 400)
                : null;
        }

        if ($method !== 'S256') {
            return self::error('invalid_request', 'code_challenge_method must be S256.', 400);
        }

        return null;
    }

    /**
     * D3: the openid scope carries a replay-binding nonce — required
     * at the push, exactly as at the authorize endpoint (RFC 9126
     * §2.1 validates as if sent directly).
     *
     * @param  array<string, mixed>  $validated
     */
    private function validateNonce(array $validated): ?JsonResponse
    {
        $scopes = preg_split('/\s+/', trim((string) ($validated['scope'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (in_array('openid', $scopes, true) && trim((string) ($validated['nonce'] ?? '')) === '') {
            return self::error('invalid_request', 'nonce is required when the openid scope is requested.', 400);
        }

        return null;
    }

    /**
     * A5 (ADR-0021): the per-profile allowed_scopes ceiling at the
     * push. RFC 9126 §2.1: the pushed request is validated as if sent
     * directly, so a scope outside the profile's ceiling is refused
     * here — league-shaped 400, no redirect context exists (or is
     * ever used) on this back channel.
     *
     * @param  array<string, mixed>  $validated
     */
    private function validateProfileScopes(string $clientId, array $validated): ?JsonResponse
    {
        $scopeIds = preg_split('/\s+/', trim((string) ($validated['scope'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $disallowed = $this->disallowedScopesForClient($scopeIds, $clientId);

        if ($disallowed !== []) {
            return self::error(
                'invalid_scope',
                'Scope not permitted for this client: '.implode(', ', $disallowed).'.',
                400
            );
        }

        return null;
    }

    /**
     * @return array{0: string, 1: JsonResponse|null}
     */
    private function authenticateClient(Request $request): array
    {
        $clientId = trim((string) $request->input('client_id', ''));
        $assertion = trim((string) $request->input('client_assertion', ''));

        if ($assertion !== '') {
            $type = trim((string) $request->input('client_assertion_type', ''));

            if ($clientId === '' || ! hash_equals(ClientAssertionVerifier::ASSERTION_TYPE, $type)) {
                return ['', self::error('invalid_client', 'Client authentication failed.', 401)];
            }

            if (! $this->assertions->verify($assertion, $clientId)) {
                return ['', self::error('invalid_client', 'Client authentication failed.', 401)];
            }

            return [$clientId, null];
        }

        $secret = trim((string) $request->input('client_secret', ''));

        if ($clientId === '' || $secret === '') {
            return ['', self::error('invalid_request', 'The request is missing a required parameter: client credentials.', 400)];
        }

        $client = Client::query()->whereKey($clientId)->first();

        if ($client === null
            || (bool) $client->getAttribute('revoked')
            || $client->getAttribute('secret') === null
            || ! Hash::check($secret, (string) $client->getAttribute('secret'))) {
            return ['', self::error('invalid_client', 'Client authentication failed.', 401)];
        }

        return [$clientId, null];
    }

    private static function error(string $error, string $description, int $status): JsonResponse
    {
        return new JsonResponse([
            'error' => $error,
            'error_description' => $description,
            'message' => $description,
        ], $status);
    }
}
