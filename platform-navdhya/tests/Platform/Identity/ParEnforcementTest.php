<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Identity\OAuth\PushedAuthorizationRequest;
use Laravel\Passport\Client;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * 9a (ADR-0021 D1) RFC 9126 enforcement at the browser-facing authorize
 * endpoint. A `require_par` (N2) client is rejected without a valid
 * single-use request_uri; a presented request_uri is validated, merged
 * over the query bag, and consumed exactly once — but only for an
 * authenticated agent, so the federation login bounce can resume with
 * the same capability. Every denial hash-chains into auth_events.
 */
final class ParEnforcementTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    private const SECRET = 'par-enforce-secret-1';

    private const REDIRECT = 'https://example.test/callback';

    private const VERIFIER = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

    private const CHALLENGE = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
            'federation.mode' => 'federated',
            'identity.hmac_secret' => 'test-hmac-secret-for-par-enforcement-32!',
        ]);
    }

    private function startOtp(string $phone): string
    {
        $res = $this->postJson('http://auth.localhost/auth/login/otp', ['phone' => $phone]);
        $res->assertStatus(202);

        return (string) $res->json('data.challenge_id');
    }

    private function parClient(bool $requirePar = true): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'PAR enforcement client',
            'secret' => \Illuminate\Support\Facades\Hash::make(self::SECRET),
            'redirect_uris' => [self::REDIRECT],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
            'allowed_grants' => ['authorization_code', 'refresh_token'],
            'allowed_scopes' => ['openid'],
            'require_par' => $requirePar,
            'require_pkce' => true,
        ]);

        return $client;
    }

    private function authorizeUrl(Client $client, array $overrides = []): string
    {
        $params = array_merge([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
            'nonce' => 'enforce-nonce-1',
        ], $overrides);

        return 'http://auth.localhost/oauth/authorize?'.http_build_query($params);
    }

    /** Mint a request_uri through the real push endpoint. */
    private function mintRequestUri(Client $client, array $overrides = []): string
    {
        $res = $this->postJson('/api/oauth/par', array_merge([
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::SECRET,
            'response_type' => 'code',
            'redirect_uri' => self::REDIRECT,
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
            'nonce' => 'enforce-nonce-1',
        ], $overrides));

        $res->assertStatus(201);

        return (string) $res->json('request_uri');
    }

    public function test_require_par_client_is_rejected_without_a_request_uri(): void
    {
        $user = User::factory()->create();
        $client = $this->parClient();

        // No request_uri and no redirect_uri to deliver an error to:
        // the only safe answer is league-shape 400 JSON, never a
        // redirect built from unverified input.
        $this->actingAs($user)->get('http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'response_type' => 'code',
            'state' => 'xyz',
        ]))->assertStatus(400)
            ->assertJsonPath('error', 'invalid_request');

        $this->assertNotNull($this->parDenials()
            ->where('reason_code', 'par_required')
            ->where('actor_client_id', (string) $client->getKey())
            ->first());
    }

    public function test_mandate_violation_redirects_only_to_a_registered_uri(): void
    {
        $client = $this->parClient();
        $user = User::factory()->create();

        // Registered redirect_uri: the error is delivered as a
        // redirect (RFC 6749 §4.1.2.1) — with iss (D2), never a 400
        // body, and never to an unverified target.
        $res = $this->actingAs($user)->get($this->authorizeUrl($client));
        $res->assertStatus(302);
        $location = (string) $res->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('invalid_request', $query['error']);
        $this->assertSame('xyz', $query['state']);
        $this->assertNotEmpty($query['iss']);

        // An unregistered redirect_uri is never a delivery target: 400
        // league-shape, no redirect, no open-redirect oracle.
        $this->actingAs($user)->get($this->authorizeUrl($client, [
            'redirect_uri' => 'https://evil.example/steal',
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');
    }

    public function test_n1_client_without_a_mandate_is_untouched(): void
    {
        $user = User::factory()->create();
        $client = $this->parClient(requirePar: false);

        $this->actingAs($user)->get($this->authorizeUrl($client))->assertOk();
        $this->assertSame(0, $this->parDenials()->count());
    }

    public function test_authenticated_resume_consumes_the_capability_exactly_once(): void
    {
        $user = User::factory()->create();
        $client = $this->parClient();
        $requestUri = $this->mintRequestUri($client);

        // The redemption URL carries ONLY client_id + request_uri; every
        // other parameter is replayed from storage, so the consent page
        // renders off the stored request and the capability is consumed.
        $this->actingAs($user)->get('http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'request_uri' => $requestUri,
        ]))->assertOk();

        $row = PushedAuthorizationRequest::resolve($requestUri);
        $this->assertNotNull($row);
        $this->assertNotNull($row->used_at, 'an authenticated resume must consume the request_uri');

        // Replay: second redemption of the same capability fails closed.
        $this->actingAs($user)->get('http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'request_uri' => $requestUri,
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        $this->assertNotNull($this->parDenials()
            ->where('reason_code', 'par_replayed')
            ->where('actor_client_id', (string) $client->getKey())
            ->first());
    }

    public function test_guest_get_validates_but_does_not_consume_so_login_can_resume(): void
    {
        $client = $this->parClient();
        $requestUri = $this->mintRequestUri($client);

        $capabilityUrl = 'http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'request_uri' => $requestUri,
        ]);

        // Guest: bounced to the unified login with the full capability
        // URL stashed as intended — NOT consumed.
        $bounce = $this->get($capabilityUrl);
        $bounce->assertStatus(302);
        $this->assertStringEndsWith('/auth/login', (string) $bounce->headers->get('Location'));
        $this->assertSame($capabilityUrl, session('url.intended'));

        $row = PushedAuthorizationRequest::resolve($requestUri);
        $this->assertNotNull($row);
        $this->assertNull($row->used_at, 'a guest GET must never consume the capability');

        // The federation ceremony completes and hands the resume target
        // back verbatim; the authenticated resume then consumes it.
        $login = $this->postJson('http://auth.localhost/auth/login/verify', [
            'challenge_id' => $this->startOtp('9898989844'),
            'token' => $this->msg91TokenFor('9898989844'),
        ]);
        $login->assertOk();

        $resume = $this->get('http://auth.localhost'.$login->json('data.redirect'));
        $resume->assertOk();

        $row->refresh();
        $this->assertNotNull($row->used_at, 'the authenticated resume must consume the capability');
    }

    public function test_unknown_expired_and_mismatched_capabilities_all_fail_closed(): void
    {
        $user = User::factory()->create();
        $client = $this->parClient();
        $other = $this->parClient();

        // Unknown request_uri.
        $this->actingAs($user)->get('http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'request_uri' => PushedAuthorizationRequest::REQUEST_URI_PREFIX.'01JZZZZZZZZZZZZZZZZZZZZZZZ',
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        $this->assertNotNull($this->parDenials()->where('reason_code', 'par_unknown')->first());

        // Expired request_uri.
        $requestUri = $this->mintRequestUri($client);
        $row = PushedAuthorizationRequest::resolve($requestUri);
        $this->assertNotNull($row);
        $row->forceFill(['expires_at' => now()->subSeconds(1)])->save();

        $this->actingAs($user)->get('http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'request_uri' => $requestUri,
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');
        $this->assertNotNull($this->parDenials()->where('reason_code', 'par_expired')->first());

        // Client mismatch: the capability names one client only.
        $requestUri = $this->mintRequestUri($client);
        $this->actingAs($user)->get('http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $other->getKey(),
            'request_uri' => $requestUri,
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');
        $this->assertNotNull($this->parDenials()->where('reason_code', 'par_client_mismatch')->first());
    }

    public function test_redemption_ignores_extraneous_query_parameters(): void
    {
        $user = User::factory()->create();
        $client = $this->parClient();
        $requestUri = $this->mintRequestUri($client);

        // Only client_id + request_uri may accompany a redemption; an
        // injected param is refused, never silently honored.
        $this->actingAs($user)->get('http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'request_uri' => $requestUri,
            'prompt' => 'none',
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        $this->assertNotNull($this->parDenials()->where('reason_code', 'par_invalid_request')->first());
    }

    public function test_stored_params_are_authoritative_over_the_query_bag(): void
    {
        $user = User::factory()->create();
        $client = $this->parClient();

        // Push a nonce of 'push-nonce-1'; the stored params win even if
        // the redemption query tries to smuggle a different nonce — but
        // extraneous params are refused, so authority is structural:
        // the query bag is REPLACED, never merged.
        $requestUri = $this->mintRequestUri($client, ['nonce' => 'stored-nonce-99']);

        $this->actingAs($user)->get('http://auth.localhost/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'request_uri' => $requestUri,
        ]))->assertOk();

        $row = PushedAuthorizationRequest::resolve($requestUri);
        $this->assertNotNull($row);
        $this->assertSame('stored-nonce-99', $row->params['nonce']);
    }

    private function parDenials(): \Illuminate\Database\Eloquent\Builder
    {
        return AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'authorization.par_denied')
            ->where('decision', 'deny');
    }
}
