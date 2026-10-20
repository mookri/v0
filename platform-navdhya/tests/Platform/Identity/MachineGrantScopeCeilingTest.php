<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Identity\OAuth\TokenExchangeGrant;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * 9b (ADR-0022): the A5 per-profile allowed_scopes ceiling extends from
 * the auth-code seams (9a) to the three machine grants — the same
 * EnforcesProfileScopes trait now governs all six enforcement points.
 *
 * client_credentials and refresh_token denials are league protocol
 * surface ({error: invalid_scope} 400, no redirect, NO audit row — the
 * ratified split matching the 9a auth-code token seam); a token-exchange
 * denial is an authorization outcome on the hash chain (D5), audited
 * with the new reason_code `scope_not_permitted`.
 *
 * Fail closed: a profile with empty allowed_scopes may mint only
 * scope-less machine tokens; an empty requested set always passes (the
 * scope-less machine norm and explicit narrowing stay untouched).
 */
final class MachineGrantScopeCeilingTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    private const MINT = TokenExchangeGrant::GRANT_IDENTIFIER;

    private const ACCESS = TokenExchangeGrant::TOKEN_TYPE;

    private const EXCHANGE_SECRET = 'exchange-secret-1';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
            'federation.mode' => 'federated',
            'identity.hmac_secret' => 'test-hmac-secret-for-federation-32-chars!!',
        ]);
    }

    // ---- client_credentials -------------------------------------------

    public function test_client_credentials_denies_a_registered_scope_outside_the_profile_ceiling(): void
    {
        // 'openid' is registered in Passport's registry — the registry
        // says yes; only the profile ceiling says no.
        $client = $this->machineClient();

        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'scope' => 'openid',
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope')
            ->assertHeaderMissing('Location');

        // Ratified split: a client_credentials ceiling denial is
        // protocol surface, never a chained authorization event.
        $this->assertNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('decision', 'deny')
            ->first());
    }

    public function test_client_credentials_issues_a_scope_the_profile_allows(): void
    {
        $client = $this->machineClient(['allowed_scopes' => ['openid']]);

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'scope' => 'openid',
        ])->assertOk()->json('access_token');

        // The granted scope survived finalizeScopes and reached the row.
        $this->assertSame(['openid'], $this->tokenRow((string) $token)->getAttribute('scopes'));
    }

    public function test_scope_less_client_credentials_passes_a_zero_ceiling(): void
    {
        $client = $this->machineClient();

        // The machine norm: no scope param at all. The ceiling gates
        // what MAY be requested, never whether a token may be minted
        // with no scopes.
        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ])->assertOk()->assertJsonPath('token_type', 'Bearer');
    }

    // ---- refresh_token -------------------------------------------------

    public function test_refresh_denies_scopes_the_profile_narrowed_away(): void
    {
        $user = User::factory()->create();
        [$client, $tokens] = $this->oidcTokensFor($user);

        // Minted while the profile allowed openid; now narrow it.
        $this->narrowProfile($client, []);

        // The refresh inherits the old token's ['openid'] — the subset
        // rule compares only to the old token and passes; the LIVE
        // profile no longer allows it. Fail closed.
        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope')
            ->assertHeaderMissing('Location');

        // The ceiling fires BEFORE the rotation transaction: no half
        // state, nothing consumed. Restoring the profile revives the
        // SAME refresh token.
        $this->narrowProfile($client, ['openid']);

        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ])->assertOk();
    }

    public function test_refresh_reissues_scopes_the_profile_still_allows(): void
    {
        $user = User::factory()->create();
        [$client, $tokens] = $this->oidcTokensFor($user);

        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ])->assertOk()->json('access_token');

        // The successor carries the same entitlement.
        $this->assertSame(['openid'], $this->tokenRow((string) $refreshed)->getAttribute('scopes'));
    }

    public function test_empty_scope_refresh_passes_a_zero_ceiling(): void
    {
        $user = User::factory()->create();
        [$client, $tokens] = $this->userTokensFor($user);

        // A scope-less lineage refreshes as it always has, profile
        // ceiling notwithstanding.
        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ])->assertOk();
    }

    // ---- token-exchange -------------------------------------------------

    public function test_exchange_denies_subject_scopes_outside_the_presenter_ceiling(): void
    {
        $user = User::factory()->create();
        [, $subjectTokens] = $this->oidcTokensFor($user); // subject carries ['openid']
        $client = $this->exchangeClient(); // allowed_scopes: [] (factory default)

        // No scope param: the exchange asks for the subject's whole
        // scope set. The subset rule passes (it compares to the
        // subject), the registry passes (openid is registered) — only
        // the PRESENTING client's profile says no.
        $this->exchange($client, (string) $subjectTokens['access_token'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope');

        // Unlike client_credentials/refresh, an exchange scope denial
        // is an authorization outcome: it lands on the chain (D5) with
        // the new reason code, bound to the presenting client.
        $deny = AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'scope_not_permitted')
            ->first();

        $this->assertNotNull($deny, 'the ceiling denial must be hash-chained');
        $this->assertSame((string) $client->getKey(), $deny->getAttribute('actor_client_id'));
    }

    public function test_exchange_issues_subject_scopes_the_presenter_profile_allows(): void
    {
        $user = User::factory()->create();
        [, $subjectTokens] = $this->oidcTokensFor($user); // subject carries ['openid']
        $client = $this->exchangeClient(['allowed_scopes' => ['openid']]);

        $token = $this->exchange($client, (string) $subjectTokens['access_token'])
            ->assertOk()
            ->json('access_token');

        $this->assertSame(['openid'], $this->tokenRow((string) $token)->getAttribute('scopes'));
    }

    public function test_exchange_narrows_explicitly_within_the_presenter_ceiling(): void
    {
        Passport::tokensCan(['openid' => 'OpenID Connect authentication.', 'read' => 'Read access.']);

        try {
            $user = User::factory()->create();

            // A subject carrying BOTH scopes, minted under a profile
            // that allowed them (the mint is not the enforcement point
            // under test here).
            [, $subjectTokens] = $this->userTokensFor($user, $this->oauthWebClient('Dual-scope web client', [
                'allowed_scopes' => ['openid', 'read'],
            ]), [], [
                'scope' => 'openid read',
                'nonce' => 'ceiling-narrow-nonce',
            ]);

            // The presenter's default ask is the subject's whole scope
            // set — 'read' busts its ceiling.
            $client = $this->exchangeClient(['allowed_scopes' => ['openid']]);
            $this->exchange($client, (string) $subjectTokens['access_token'])
                ->assertStatus(400)
                ->assertJsonPath('error', 'invalid_scope');

            // An explicit narrowing to what the profile allows fits:
            // entitlement may be dropped, never gained.
            $token = $this->exchange($client, (string) $subjectTokens['access_token'], ['scope' => 'openid'])
                ->assertOk()
                ->json('access_token');

            $this->assertSame(['openid'], $this->tokenRow((string) $token)->getAttribute('scopes'));
        } finally {
            Passport::tokensCan(['openid' => 'OpenID Connect authentication.']);
        }
    }

    // ---- helpers --------------------------------------------------------

    /**
     * Confidential machine client profiled for client_credentials.
     *
     * @param  array<string, mixed>  $profileOverrides
     */
    private function machineClient(array $profileOverrides = []): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'Ceiling machine client',
            'secret' => self::CLIENT_SECRET,
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create(array_merge([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
            'allowed_grants' => ['client_credentials'],
            // Factory default: allowed_scopes = [] (fail closed).
        ], $profileOverrides));

        return $client;
    }

    /**
     * Confidential first-party service client registered for the
     * exchange on BOTH enforcement surfaces (client row + profile) —
     * the BFF machine shape. Profile defaults to allowed_scopes = [].
     *
     * @param  array<string, mixed>  $profileOverrides
     */
    private function exchangeClient(array $profileOverrides = []): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'Ceiling exchange BFF',
            'secret' => self::EXCHANGE_SECRET,
            'redirect_uris' => [],
            'grant_types' => ['client_credentials', self::MINT],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create(array_merge([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
            'allowed_grants' => ['client_credentials', self::MINT],
        ], $profileOverrides));

        return $client;
    }

    /**
     * OIDC ceremony through the shared helper: an access token (and its
     * refresh lineage) carrying the openid scope — the profile allows
     * it at mint time, so narrowing later is a real entitlement
     * change, not a broken mint.
     *
     * @return array{0: Client, 1: array{access_token: string, refresh_token: string}}
     */
    private function oidcTokensFor(User $user): array
    {
        return $this->userTokensFor($user, $this->oauthWebClient('Ceiling web client', [
            'allowed_scopes' => ['openid'],
        ]), [], [
            'scope' => 'openid',
            'nonce' => 'ceiling-refresh-nonce',
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function exchange(Client $client, string $subjectToken, array $extra = []): TestResponse
    {
        return $this->postJson('/oauth/token', array_merge([
            'grant_type' => self::MINT,
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::EXCHANGE_SECRET,
            'subject_token' => $subjectToken,
            'subject_token_type' => self::ACCESS,
        ], $extra));
    }

    /**
     * @param  list<string>  $allowedScopes
     */
    private function narrowProfile(Client $client, array $allowedScopes): void
    {
        $profile = OAuthClientProfile::query()
            ->where('client_id', (string) $client->getKey())
            ->firstOrFail();
        $profile->allowed_scopes = $allowedScopes;
        $profile->save();
    }

    /**
     * The persisted row behind an issued JWT access token — scopes are
     * authoritative here (never decoded from the token itself).
     */
    private function tokenRow(string $accessToken): Token
    {
        $segments = explode('.', $accessToken);
        $this->assertCount(3, $segments);

        $claims = json_decode(base64_decode(strtr($segments[1], '-_', '+/')), true);
        $this->assertIsArray($claims);
        $this->assertArrayHasKey('jti', $claims);

        $row = Passport::token()->newQuery()->find((string) $claims['jti']);
        $this->assertNotNull($row, 'the issued token must persist a row');

        return $row;
    }
}
