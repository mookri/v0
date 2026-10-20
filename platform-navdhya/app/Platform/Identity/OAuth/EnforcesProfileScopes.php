<?php

namespace App\Platform\Identity\OAuth;

use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * The per-profile allowed_scopes ceiling (ADR-0021 A5, extended from
 * the auth-code seams to the machine grants by 9b / ADR-0022), shared
 * by ALL SIX enforcement seams so the rule can never drift between
 * them:
 *
 *  - auth-code (9a): authorize endpoint, token exchange, PAR push;
 *  - machine   (9b): client_credentials, refresh_token, token-exchange.
 *
 * Fail closed: a client with no profile row (or a profile whose
 * allowed_scopes is empty) may request nothing, so registering a new
 * scope in Passport's registry never implicitly grants it to every
 * client, and a deleted profile revokes entitlement rather than
 * widening it. An empty requested set always passes — the ceiling
 * gates what MAY be requested, not whether a token may be minted with
 * no scopes at all (pre-9a `scope=` flows and the scope-less machine
 * norm stay untouched).
 *
 * The three grant seams call enforceProfileScopes() and let league's
 * protocol error surface (redirect 302 on authorize, 400 JSON at the
 * token endpoint); the PAR push seam and the audited token-exchange
 * seam call disallowedScopesForClient() directly and shape their own
 * deny (JsonResponse / hash-chained audit).
 */
trait EnforcesProfileScopes
{
    /**
     * The scopes this client's profile allows. A missing profile row
     * allows nothing (fail closed).
     *
     * @return list<string>
     */
    protected function allowedScopesForClient(string $clientId): array
    {
        $profile = OAuthClientProfile::query()
            ->where('client_id', $clientId)
            ->first();

        /** @var list<string> $allowed */
        $allowed = $profile === null ? [] : array_values((array) $profile->allowed_scopes);

        return $allowed;
    }

    /**
     * The requested scopes the profile does NOT allow (empty = the
     * whole request fits the ceiling). Evaluated as a set — duplicates
     * and ordering are irrelevant.
     *
     * @param  list<string>  $scopeIds
     * @return list<string>
     */
    protected function disallowedScopesForClient(array $scopeIds, string $clientId): array
    {
        if ($scopeIds === []) {
            return [];
        }

        $allowed = $this->allowedScopesForClient($clientId);

        return array_values(array_filter(
            $scopeIds,
            static fn (string $scopeId): bool => ! in_array($scopeId, $allowed, true)
        ));
    }

    /**
     * Throw league's invalid_scope for the first scope outside the
     * ceiling, redirect-aware: with a verified redirect_uri the
     * authorize seam answers a 302 with state/iss; without one (every
     * token-endpoint seam, machine or auth-code) league answers a 400
     * invalid_scope JSON body, exactly as it does for an unregistered
     * scope at the same seam.
     *
     * @param  list<string>  $scopeIds
     */
    protected function enforceProfileScopes(array $scopeIds, string $clientId, ?string $redirectUri = null): void
    {
        $disallowed = $this->disallowedScopesForClient($scopeIds, $clientId);

        if ($disallowed !== []) {
            throw OAuthServerException::invalidScope($disallowed[0], $redirectUri);
        }
    }
}
