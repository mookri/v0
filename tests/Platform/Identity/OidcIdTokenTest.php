<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * 9a (ADR-0021 D3/A5) the OIDC Authorization Code flow: a mandatory,
 * exactly-bounded nonce on every openid request; an ID token minted
 * ONLY by an openid code exchange (RS256, kid resolvable against the
 * published JWKS, signature verifiable with the published public key,
 * byte-equal issuer, ceremony facts carried from the login session);
 * concurrent-tab displacement failing closed at the BFF; and the A5
 * per-profile allowed_scopes ceiling enforced at the authorize and
 * token seams (the push seam lives in PushedAuthorizationRequestTest).
 */
final class OidcIdTokenTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;
    use IssuesUserTokens;

    private const HOST = 'http://auth.localhost';

    private const REDIRECT = 'https://example.test/callback';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();

        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
            'federation.mode' => 'federated',
            'identity.hmac_secret' => 'test-hmac-secret-for-federation-32-chars!!',
        ]);
    }

    /**
     * A profiled web client whose profile allows the openid scope.
     *
     * @param  array<string, mixed>  $profileOverrides
     */
    private function oidcClient(array $profileOverrides = []): Client
    {
        return $this->oauthWebClient('OIDC web client', array_merge([
            'allowed_scopes' => ['openid'],
        ], $profileOverrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function authorizeParams(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
            'nonce' => 'idtoken-nonce-1',
        ], $overrides);
    }

    /**
     * Authorize + consent-approve (default host, acting agent);
     * returns the issued code.
     *
     * @param  array<string, mixed>  $authorizeOverrides
     */
    private function mintCode(Client $client, User $user, array $authorizeOverrides = []): string
    {
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query($this->authorizeParams($client, $authorizeOverrides)))
            ->assertOk();

        $approve = $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ]);
        $approve->assertStatus(302);
        $location = (string) $approve->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return (string) $query['code'];
    }

    private function exchange(Client $client, string $code): TestResponse
    {
        return $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => self::REDIRECT,
            'code' => $code,
            'code_verifier' => self::VERIFIER,
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} header, claims
     */
    private function decodeJwt(string $jwt): array
    {
        $segments = explode('.', $jwt);
        $this->assertCount(3, $segments);

        $header = json_decode($this->base64UrlDecode($segments[0]), true);
        $claims = json_decode($this->base64UrlDecode($segments[1]), true);

        $this->assertIsArray($header);
        $this->assertIsArray($claims);

        return [$header, $claims];
    }

    private function base64UrlDecode(string $segment): string
    {
        $base64 = strtr($segment, '-_', '+/');
        $padded = $base64.str_repeat('=', (4 - strlen($base64) % 4) % 4);

        return (string) base64_decode($padded, true);
    }

    private function assertSignatureVerifies(string $jwt): void
    {
        $segments = explode('.', $jwt);
        $publicKey = file_get_contents(storage_path('oauth-public.key'));
        $this->assertNotFalse($publicKey);

        $this->assertSame(1, openssl_verify(
            $segments[0].'.'.$segments[1],
            $this->base64UrlDecode($segments[2]),
            $publicKey,
            OPENSSL_ALGO_SHA256
        ), 'the ID token signature must verify against the published public key');
    }

    private function startOtp(string $phone): string
    {
        $res = $this->postJson(self::HOST.'/auth/login/otp', ['phone' => $phone]);
        $res->assertStatus(202);

        return (string) $res->json('data.challenge_id');
    }

    private function loginViaOtp(string $phone): TestResponse
    {
        return $this->postJson(self::HOST.'/auth/login/verify', [
            'challenge_id' => $this->startOtp($phone),
            'token' => $this->msg91TokenFor($phone),
        ]);
    }

    public function test_openid_scope_requires_a_bounded_nonce_at_authorize(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // Missing nonce: league-shape 400 JSON, never a redirect — the
        // request dies before any consent can be minted.
        $missing = $this->authorizeParams($client);
        unset($missing['nonce']);

        $res = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query($missing));
        $res->assertStatus(400)
            ->assertJsonPath('error', 'invalid_request')
            ->assertHeaderMissing('Location');
        $this->assertSame(
            'A nonce is required when the openid scope is requested.',
            $res->json('hint')
        );

        // Beyond the 512-character bound: rejected identically.
        $overlong = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['nonce' => str_repeat('n', 513)])
        ));
        $overlong->assertStatus(400)
            ->assertJsonPath('error', 'invalid_request')
            ->assertHeaderMissing('Location');
        $this->assertSame('The nonce must be at most 512 characters.', $overlong->json('hint'));

        // Control: a bounded nonce reaches consent — the 400s above are
        // nonce-specific, not a broken client.
        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client)
        ))->assertOk();
    }

    public function test_openid_exchange_mints_an_rs256_id_token_with_shared_issuer(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $tokens = $this->exchange($client, $this->mintCode($client, $user))->assertOk()->json();
        $this->assertArrayHasKey('id_token', $tokens);
        $this->assertArrayHasKey('access_token', $tokens);

        [$header, $claims] = $this->decodeJwt((string) $tokens['id_token']);

        // Header: RS256 + typ + the RFC 7638 kid published in JWKS.
        $this->assertSame('RS256', $header['alg']);
        $this->assertSame('JWT', $header['typ']);
        $jwks = $this->getJson('/.well-known/jwks.json')->assertOk()->json('keys');
        $this->assertSame($jwks[0]['kid'], $header['kid']);
        $this->assertSignatureVerifies((string) $tokens['id_token']);

        // Claims: one shared issuer derivation (byte-equal to discovery),
        // the code's client as audience, nonce round-trip, 10-min expiry.
        $this->assertSame(IssuerIdentifier::value(), $claims['iss']);
        $this->assertSame(
            $this->getJson('/.well-known/openid-configuration')->assertOk()->json('issuer'),
            $claims['iss']
        );
        $this->assertSame((string) $client->getKey(), $claims['aud']);
        $this->assertSame('idtoken-nonce-1', $claims['nonce']);
        $this->assertSame(600, $claims['exp'] - $claims['iat']);

        // sub follows the account's stable identifier (userinfo parity).
        $expectedSub = $user->external_id !== null && $user->external_id !== ''
            ? (string) $user->external_id
            : (string) $user->getAuthIdentifier();
        $this->assertSame($expectedSub, $claims['sub']);

        // No login ceremony on this session: auth_time is present,
        // amr/acr are ABSENT (never invented), a per-session sid rides.
        $this->assertIsInt($claims['auth_time']);
        $this->assertGreaterThan(time() - 60, $claims['auth_time']);
        $this->assertLessThanOrEqual(time(), $claims['auth_time']);
        $this->assertArrayNotHasKey('amr', $claims);
        $this->assertArrayNotHasKey('acr', $claims);
        $this->assertIsString($claims['sid'] ?? null);
        $this->assertNotSame('', $claims['sid']);
    }

    public function test_full_ceremony_issues_an_id_token_bound_to_the_login_session(): void
    {
        $client = $this->oidcClient();
        $phone = '9898989864';

        $params = $this->authorizeParams($client, ['nonce' => 'ceremony-nonce-42']);
        // Canonical query for url.intended comparison (sorted keys).
        ksort($params);
        $authorizeUrl = self::HOST.'/oauth/authorize?'.http_build_query($params);

        // Guest: bounced to the unified login, intended URL stashed.
        $bounce = $this->get($authorizeUrl);
        $bounce->assertStatus(302);
        $this->assertStringEndsWith('/auth/login', (string) $bounce->headers->get('Location'));
        $this->assertSame($authorizeUrl, session('url.intended'));

        // The OTP ceremony completes and hands the resume target back.
        $login = $this->loginViaOtp($phone);
        $login->assertOk();
        $resume = (string) $login->json('data.redirect');
        $this->assertStringStartsWith('/oauth/authorize?', $resume);

        // Authenticated resume renders consent (the nonce re-validated
        // and re-stashed across the login regeneration).
        $this->get(self::HOST.$resume)->assertOk();

        $approve = $this->post(self::HOST.'/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ]);
        $approve->assertStatus(302);
        $location = (string) $approve->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
        $this->assertNotEmpty($query['code']);

        // The only exit for an ID token: the openid code exchange.
        $tokens = $this->exchange($client, (string) $query['code'])->assertOk()->json();
        $this->assertArrayHasKey('id_token', $tokens);

        [$header, $claims] = $this->decodeJwt((string) $tokens['id_token']);
        $this->assertSame('RS256', $header['alg']);
        $jwks = $this->getJson('/.well-known/jwks.json')->assertOk()->json('keys');
        $this->assertSame($jwks[0]['kid'], $header['kid']);
        $this->assertSignatureVerifies((string) $tokens['id_token']);

        $this->assertSame('http://auth.localhost', $claims['iss']);
        $this->assertSame(IssuerIdentifier::value(), $claims['iss']);
        $this->assertSame((string) $client->getKey(), $claims['aud']);
        $this->assertSame('ceremony-nonce-42', $claims['nonce']);
        $this->assertSame(600, $claims['exp'] - $claims['iat']);

        // The provisioned federation account is the subject.
        $this->assertStringStartsWith('fed_', (string) $claims['sub']);

        // Ceremony facts exactly as the login ceremony recorded them:
        // phone OTP alone ⇒ amr ['otp'], aal1, fresh sid, auth_time at
        // the moment identity landed on the session.
        $ceremony = session('oidc.ceremony');
        $this->assertIsArray($ceremony);
        $this->assertSame($ceremony['auth_time'], $claims['auth_time']);
        $this->assertSame(['otp'], $claims['amr']);
        $this->assertSame('aal1', $claims['acr']);
        $this->assertSame($ceremony['sid'], $claims['sid']);
    }

    public function test_seeded_session_ceremony_facts_mint_verbatim(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // An MFA ceremony (otp + totp) as a prior login left it.
        $this->withSession(['oidc.ceremony' => [
            'auth_time' => 1700000000,
            'amr' => ['otp', 'totp'],
            'acr' => 'aal2',
            'sid' => '01JSEEDSID0000000000000000',
        ]]);

        $tokens = $this->exchange($client, $this->mintCode($client, $user))->assertOk()->json();
        [, $claims] = $this->decodeJwt((string) $tokens['id_token']);

        $this->assertSame(1700000000, $claims['auth_time']);
        $this->assertSame(['otp', 'totp'], $claims['amr']);
        $this->assertSame('aal2', $claims['acr']);
        $this->assertSame('01JSEEDSID0000000000000000', $claims['sid']);
        $this->assertSame('idtoken-nonce-1', $claims['nonce']);
    }

    public function test_id_token_never_appears_on_non_openid_responses(): void
    {
        $user = User::factory()->create();

        // A non-openid code exchange: tokens, no identity assertion.
        [$client, $tokens] = $this->userTokensFor($user);
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayNotHasKey('id_token', $tokens);

        // Refresh rides league's own grant — still no ID token.
        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $refreshed->assertOk();
        $this->assertArrayNotHasKey('id_token', $refreshed->json());

        // Machine tokens: client_credentials never mints one either.
        $service = new Client();
        $service->forceFill([
            'name' => 'OIDC machine client',
            'secret' => 'oidc-machine-secret-1',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $service->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $service->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
            'allowed_grants' => ['client_credentials'],
            'allowed_scopes' => [],
        ]);

        $machine = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $service->getKey(),
            'client_secret' => 'oidc-machine-secret-1',
        ]);
        $machine->assertOk();
        $this->assertArrayHasKey('access_token', $machine->json());
        $this->assertArrayNotHasKey('id_token', $machine->json());
    }

    public function test_last_validated_nonce_wins_across_concurrent_tabs(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // Tab 1 validates its nonce…
        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['nonce' => 'tab-nonce-1'])
        ))->assertOk();

        // …tab 2 displaces it before consent (the stash is keyed per
        // client, so the last validated nonce wins).
        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['nonce' => 'tab-nonce-2'])
        ))->assertOk();

        // Consent is the POST straight off tab 2 (no intervening
        // authorize GET): the code binds tab 2's nonce.
        $approve = $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ]);
        $approve->assertStatus(302);
        $location = (string) $approve->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $tokens = $this->exchange($client, (string) $query['code'])->assertOk()->json();
        [, $claims] = $this->decodeJwt((string) $tokens['id_token']);

        // The consent binds to the last validated nonce: a BFF that
        // stored tab 1's nonce MUST reject this token — displacement
        // fails closed at the client, never open at the server.
        $this->assertSame('tab-nonce-2', $claims['nonce']);
    }

    public function test_profile_scope_ceiling_denies_at_the_authorize_endpoint(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient(['allowed_scopes' => []]);

        // A valid nonce rides along: the denial isolates the ceiling.
        $res = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client)
        ));

        $res->assertStatus(302);
        $location = (string) $res->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('invalid_scope', $query['error']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
    }

    public function test_missing_profile_fails_closed_at_the_authorize_endpoint(): void
    {
        $user = User::factory()->create();

        $client = new Client();
        $client->forceFill([
            'name' => 'Unprofiled web client',
            'secret' => 'unprofiled-secret-1',
            'redirect_uris' => [self::REDIRECT],
            'grant_types' => ['authorization_code'],
            'revoked' => false,
        ]);
        $client->save();

        // No profile row ⇒ nothing allowed: openid dies at the A5
        // ceiling (registered scope or not, never implicitly granted).
        $res = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client)
        ));

        $res->assertStatus(302);
        $location = (string) $res->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('invalid_scope', $query['error']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);

        // The empty request is not a scope request: pre-9a scope=
        // flows stay untouched under the same fail-closed profile rule.
        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['scope' => ''])
        ))->assertOk();
    }

    public function test_profile_scope_ceiling_denies_at_the_token_endpoint(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // Mint while the ceiling still allows openid…
        $code = $this->mintCode($client, $user);

        // …then narrow it: entitlement is never read from the code
        // payload alone.
        $profile = OAuthClientProfile::query()
            ->where('client_id', (string) $client->getKey())
            ->firstOrFail();
        $profile->allowed_scopes = [];
        $profile->save();

        // The exchange fails closed: league-shape 400, no redirect.
        $this->exchange($client, $code)
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope')
            ->assertHeaderMissing('Location');
    }

    public function test_empty_scope_request_passes_a_zero_ceiling(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient(['allowed_scopes' => []]);

        // scope= is not a scope request: the ceiling gates what MAY be
        // requested, not whether a token may be minted with no scopes.
        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['scope' => ''])
        ))->assertOk();

        $tokens = $this->exchange($client, $this->mintCode($client, $user, ['scope' => '']))
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayNotHasKey('id_token', $tokens);
    }
}
