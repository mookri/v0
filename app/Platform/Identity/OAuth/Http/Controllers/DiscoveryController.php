<?php

namespace App\Platform\Identity\OAuth\Http\Controllers;

use App\Models\User;
use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OAuth/OIDC discovery surfaces (SEC-002 §2.1; 9a ADR-0021): RFC 8414
 * authorization-server metadata, the OIDC `openid-configuration`
 * document (now served — ID tokens are issued, 9a), JWKS public keys,
 * and a userinfo endpoint.
 *
 * JWKS (ADR-0020 D3): every key carries an RFC 7638 thumbprint `kid`,
 * and retired public halves archived by `auth:rotate-signing-key` are
 * dual-published alongside the current key so rotation overlaps —
 * already-issued tokens keep verifying while new tokens use the new
 * key. League mints kid-less token headers, so consumers resolve by
 * trying the (small, kid-labelled) key set; the kid still lets them
 * cache, dedupe and detect rotation.
 */
final class DiscoveryController
{
    /**
     * Bound on retired keys published: overlap is a window, not an
     * archive — the newest few cover every token still in flight.
     */
    private const MAX_RETIRED_KEYS = 5;

    public function metadata(): JsonResponse
    {
        // The document describes THE authorization server, so it
        // advertises the configured issuer identifier (ADR-0020
        // federation.issuer: prod auth.navdhya.com, dev auth.localhost)
        // whenever one exists — never the app URL of whichever host
        // happened to serve the request. Direct-mode deployments that
        // set no issuer keep the historical app-url base. One shared
        // derivation (IssuerIdentifier) backs this, the RFC 9207 `iss`
        // parameter and ID-token minting — they must agree byte for
        // byte.
        $base = IssuerIdentifier::value();

        return new JsonResponse($this->oauthDocument($base), 200, ['Cache-Control' => 'public, max-age=3600']);
    }

    /**
     * OIDC discovery (OIDC Discovery 1.0 §3): the same document RFC
     * 8414 serves, plus the OIDC-only fields — signing algorithms,
     * the `openid` scope, subject type and nonce support. Deliberately
     * omits `require_pushed_request_parameters`: PAR is a per-client
     * mandate (D1), never a global one, so advertising a server-wide
     * mandate would misdescribe N1 clients.
     */
    public function openidConfiguration(): JsonResponse
    {
        $base = IssuerIdentifier::value();

        $document = $this->oauthDocument($base);

        $document['subject_types_supported'] = ['public'];
        $document['id_token_signing_alg_values_supported'] = ['RS256'];
        $document['scopes_supported'] = ['openid'];
        $document['nonce_supported'] = true;

        // OIDC Back-Channel Logout 1.0 (A-SEC-004-5): logout tokens are
        // pushed to registered backchannel_logout_uri endpoints, bound
        // to the OP session id (sid) carried in every ID token here.
        $document['backchannel_logout_supported'] = true;
        $document['backchannel_logout_session_supported'] = true;

        return new JsonResponse($document, 200, ['Cache-Control' => 'public, max-age=3600']);
    }

    /**
     * The shared OAuth document both discovery URLs serve.
     *
     * @return array<string, mixed>
     */
    private function oauthDocument(string $base): array
    {
        return [
            'issuer' => $base,
            'authorization_endpoint' => $base.'/oauth/authorize',
            'token_endpoint' => $base.'/oauth/token',
            'userinfo_endpoint' => $base.'/oauth/userinfo',
            'jwks_uri' => $base.'/.well-known/jwks.json',
            'revocation_endpoint' => $base.'/api/oauth/revoke',
            'pushed_authorization_request_endpoint' => $base.'/api/oauth/par',
            // OIDC RP-Initiated Logout 1.0 (A-SEC-004-5): where the
            // browser takes the RP's "sign out" — advertised on both
            // documents because RP tooling reads either.
            'end_session_endpoint' => $base.'/oauth/logout',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token', 'client_credentials', 'urn:ietf:params:oauth:grant-type:token-exchange'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post', 'private_key_jwt'],
            'code_challenge_methods_supported' => ['S256'],
            'request_uri_parameter_supported' => true,
            // RFC 9207: every redirect-based authorization response
            // carries `iss` — success and error alike (D2).
            'authorization_response_iss_parameter_supported' => true,
        ];
    }

    public function jwks(): JsonResponse
    {
        $pems = [];

        $current = self::publicKey();

        if ($current !== '') {
            $pems[] = $current;
        }

        foreach (self::retiredPublicKeys() as $retired) {
            $pems[] = $retired;
        }

        $keys = [];
        $seen = [];

        foreach ($pems as $pem) {
            $jwk = Jwk::fromPem($pem);

            // Unreadable files and duplicate kids (current also
            // archived, double rotations) are skipped, never served.
            if ($jwk === null || isset($seen[$jwk['kid']])) {
                continue;
            }

            $seen[$jwk['kid']] = true;
            $keys[] = $jwk;
        }

        if ($keys === []) {
            return new JsonResponse([
                'error' => ['code' => 'service_unavailable', 'message' => 'Signing keys unavailable.'],
            ], 503);
        }

        return new JsonResponse([
            'keys' => $keys,
        ], 200, ['Cache-Control' => 'public, max-age=3600']);
    }

    public function userinfo(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            // Machine principals hold no profile: userinfo is identity
            // surface for users (humans via any accepted credential).
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.'],
            ], 401);
        }

        return new JsonResponse([
            'sub' => $user->external_id !== null && $user->external_id !== ''
                ? (string) $user->external_id
                : (string) $user->getAuthIdentifier(),
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
            'phone_number' => $user->phone,
            'phone_number_verified' => $user->phone_verified_at !== null,
        ]);
    }

    /**
     * Retired public halves, newest first: files named <kid>.pub as
     * written by auth:rotate-signing-key. Missing directory = no
     * overlap window yet (fresh deployment), not an error. Public for
     * the end-session endpoint's id_token_hint verification — one key
     * source for JWKS publication and hint validation (A-SEC-004-5),
     * never two walks that can disagree.
     *
     * @return list<string>
     */
    public static function retiredPublicKeys(): array
    {
        $dir = rtrim((string) config('federation.retired_signing_keys_dir', ''), '/\\');

        if ($dir === '' || ! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.DIRECTORY_SEPARATOR.'*.pub');

        if (! is_array($files) || $files === []) {
            return [];
        }

        usort($files, static fn (string $a, string $b): int => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));

        $pems = [];

        foreach (array_slice($files, 0, self::MAX_RETIRED_KEYS) as $file) {
            $contents = @file_get_contents($file);

            if (is_string($contents) && trim($contents) !== '') {
                $pems[] = $contents;
            }
        }

        return $pems;
    }

    /**
     * The current JWKS key source — resolved through the signing
     * custody (config PEM / key file under file custody, the KMS
     * public half under remote custody; the same source the ID-token
     * kid uses, so JWKS and kid cannot drift). '' when the custody has
     * no readable public half: jwks() then serves retired keys only,
     * or 503.
     */
    private static function publicKey(): string
    {
        try {
            return SigningCustody::resolve()->publicKeyPem();
        } catch (FederationConfigurationException) {
            return '';
        }
    }
}
