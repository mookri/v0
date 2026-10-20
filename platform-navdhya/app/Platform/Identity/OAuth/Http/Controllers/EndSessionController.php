<?php

namespace App\Platform\Identity\OAuth\Http\Controllers;

use App\Models\User;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use App\Platform\Identity\SessionTerminator;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * OIDC RP-Initiated Logout 1.0 end-session endpoint (SEC-004 /
 * A-SEC-004-5), served at /oauth/logout (advertised as
 * end_session_endpoint in discovery). GET and POST both (spec
 * requires both; POST rides the CSRF exception for cross-origin RP
 * form posts).
 *
 * Contract, fail closed:
 *
 *  1. id_token_hint (optional) is verified against the CURRENT and
 *     RETIRED custody public keys — signature + iss + sub, exp/nbf
 *     deliberately ignored per spec (a logout hint is expected to be
 *     expired). Any failure means "unidentified requester": the
 *     session still terminates, the redirect never happens, and a
 *     deny row hits the chain (abuse visibility).
 *  2. Termination is unconditional — even an invalid hint ends the
 *     OP session (the user asked to log out; refusal would strand
 *     them), shared with app logout through SessionTerminator so
 *     audit + back-channel propagation are one path.
 *  3. The redirect happens only for a REGISTERED
 *     post_logout_redirect_uri of the client the valid hint (or, when
 *     no hint was sent at all, the bare client_id) identifies — exact
 *     match, never derived from redirect_uris, never accepted on a
 *     failed hint. state rides along when supplied.
 *  4. Everything else lands on the neutral logged-out page: no open
 *     redirect exists on this endpoint by construction.
 */
final class EndSessionController
{
    private const MAX_HINT_LENGTH = 4096;

    private const MAX_URI_LENGTH = 2048;

    private const MAX_STATE_LENGTH = 512;

    public function __construct(
        private readonly SessionTerminator $terminator,
        private readonly AuthEventRecorder $audit,
    ) {
    }

    public function __invoke(Request $request): Response|\Illuminate\Contracts\View\View
    {
        $hint = $request->input('id_token_hint');
        $hint = is_string($hint) && $hint !== '' && strlen($hint) <= self::MAX_HINT_LENGTH
            ? $hint
            : null;
        $clientIdHint = $request->input('client_id');
        $clientIdHint = is_string($clientIdHint) && $clientIdHint !== '' && strlen($clientIdHint) <= 80
            ? $clientIdHint
            : null;
        $postLogout = $request->input('post_logout_redirect_uri');
        $postLogout = is_string($postLogout) && $postLogout !== '' && strlen($postLogout) <= self::MAX_URI_LENGTH
            ? $postLogout
            : null;
        $state = $request->input('state');
        $state = is_string($state) && $state !== '' && strlen($state) <= self::MAX_STATE_LENGTH
            ? $state
            : null;

        // Resolve the RP this request may redirect to — null means "no
        // redirect under any circumstances".
        $client = null;
        $hintRejected = false;

        if ($hint !== null) {
            $client = $this->clientFromHint($hint);

            if ($client === null) {
                // A sent-but-invalid hint is an attempted (or broken)
                // logout: never fall through to a client_id redirect.
                $hintRejected = true;
            }
        } elseif ($clientIdHint !== null) {
            $candidate = Client::query()->find($clientIdHint);
            $client = $candidate !== null && ! $candidate->revoked ? $candidate : null;
        }

        $target = $this->redirectTarget($client, $postLogout);

        $result = $this->terminator->terminate($request, 'rp_initiated');

        if ($hintRejected) {
            $this->recordRejectedHint($result['user']);
        }

        if ($target !== null) {
            if ($state !== null) {
                $separator = str_contains($target, '?') ? '&' : '?';
                $target .= $separator.http_build_query(['state' => $state]);
            }

            return redirect()->away($target);
        }

        return response()->view('oauth.logged-out');
    }

    /**
     * Validate an id_token_hint and resolve its audience to a live
     * client. Null on ANY failure: bad signature (no key in the
     * current+retired set verifies), wrong issuer, missing/empty sub,
     * unknown or revoked client. exp/nbf are NOT checked — RP-Initiated
     * Logout explicitly relies on hints that outlive their 10-minute
     * window.
     */
    private function clientFromHint(string $jwt): ?Client
    {
        $segments = explode('.', $jwt);

        if (count($segments) !== 3 || $segments[0] === '' || $segments[1] === '' || $segments[2] === '') {
            return null;
        }

        $signature = $this->base64UrlDecode($segments[2]);

        if ($signature === '') {
            return null;
        }

        $signedInput = $segments[0].'.'.$segments[1];
        $verified = false;

        foreach ($this->verificationKeys() as $pem) {
            if (openssl_verify($signedInput, $signature, $pem, OPENSSL_ALGO_SHA256) === 1) {
                $verified = true;
                break;
            }
        }

        if (! $verified) {
            return null;
        }

        $claims = json_decode($this->base64UrlDecode($segments[1]), true);

        if (! is_array($claims)) {
            return null;
        }

        if (($claims['iss'] ?? null) !== IssuerIdentifier::value()) {
            return null;
        }

        $aud = $claims['aud'] ?? null;
        $aud = is_string($aud) && $aud !== '' && strlen($aud) <= 80 ? $aud : null;
        $sub = $claims['sub'] ?? null;

        if ($aud === null || ! is_string($sub) || $sub === '') {
            return null;
        }

        $client = Client::query()->find($aud);

        return $client !== null && ! $client->revoked ? $client : null;
    }

    /**
     * The only redirect shape this endpoint ever emits: an exact match
     * against the client's REGISTERED post-logout URIs (profile field,
     * fail-closed when absent). The client must exist and the target
     * must be a web URL.
     */
    private function redirectTarget(?Client $client, ?string $postLogout): ?string
    {
        if ($client === null || $postLogout === null) {
            return null;
        }

        $scheme = strtolower((string) parse_url($postLogout, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $profile = OAuthClientProfile::query()
            ->where('client_id', (string) $client->getKey())
            ->first();
        $registered = $profile->post_logout_redirect_uris ?? [];

        if (! is_array($registered)) {
            return null;
        }

        foreach ($registered as $candidate) {
            if (is_string($candidate) && hash_equals($candidate, $postLogout)) {
                return $postLogout;
            }
        }

        return null;
    }

    /**
     * Current custody key first, then the retired overlap set — the
     * same key sources JWKS publishes (a hint minted before a rotation
     * must still end its session; A-SEC-003-1 overlap transfers).
     *
     * @return list<string>
     */
    private function verificationKeys(): array
    {
        $keys = [];

        try {
            $current = SigningCustody::resolve()->publicKeyPem();

            if ($current !== '') {
                $keys[] = $current;
            }
        } catch (FederationConfigurationException) {
            // No current key readable: retired keys may still verify.
        }

        foreach (DiscoveryController::retiredPublicKeys() as $retired) {
            $keys[] = $retired;
        }

        return $keys;
    }

    private function base64UrlDecode(string $segment): string
    {
        $base64 = strtr($segment, '-_', '+/');
        $padded = $base64.str_repeat('=', (4 - strlen($base64) % 4) % 4);

        return (string) base64_decode($padded, true);
    }

    /**
     * Deny row for a rejected hint — the session.logout row above it
     * still records the termination itself (allow, rp_initiated).
     */
    private function recordRejectedHint(?User $user): void
    {
        try {
            $this->audit->record([
                'event_type' => 'session.end_session_request',
                'decision' => 'deny',
                'subject_id' => $user !== null ? (string) $user->getAuthIdentifier() : null,
                'source_audience' => 'federation',
                'risk_tier' => 'T1',
                'reason_code' => 'invalid_id_token_hint',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
