<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\Federation\RevocationStatusCache;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * 9c — fail-closed revocation (ADR-0023): the six verified gaps between
 * the SEC-015 §8.1 trigger list / RFC 7009 ownership rule and what
 * actually invalidates tokens, sessions and RevocationStatusCache rows.
 *
 *  C1 RFC 7009 §2.1/§5: a foreign client cannot revoke another client's
 *     token — 400 invalid_grant (ratified D-9c); unknown stays 200.
 *  C2 deactivation is a bulk trigger like suspension (all tokens, all
 *     sessions) AND the session-shaped branch of AuthenticateAny gates
 *     lifecycle status at the enforcement point itself.
 *  C3 console revocation sites invalidate the status cache like the
 *     HTTP mirror (0 s, not the 30 s TTL).
 *  C4 every executed kill lands on the D5 hash chain with its reason
 *     code (refusals land nowhere).
 *  C5 membership deactivation revokes that tenant's lineages only.
 *  C6 introspection fails closed: forged signature, inactive owner and
 *     missing expiry all answer active:false.
 *
 * Every leg is positive AND negative: the denial and the survival of
 * what must survive.
 */
final class FailClosedRevocationTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;
    use IssuesUserTokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeMsg91();

        // Revocation events chain through the D5 recorder, whose
        // pseudonymization refuses to run without the identity secret
        // (kit convention: each test supplies its own).
        config(['identity.hmac_secret' => 'fail-closed-revocation-hmac-32!']);
    }

    // ---- C1: RFC 7009 §2.1 ownership ---------------------------------

    public function test_a_foreign_client_cannot_revoke_another_clients_access_token(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $tokens] = $this->userTokensFor($user);
        $foreign = $this->serviceClient();
        $jti = $this->jti($tokens['access_token']);
        auth()->logout();

        // A foreign client presents a real, live token it does not own.
        $this->revoke((string) $foreign->getKey(), 'nine-c-service-secret', $tokens['access_token'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        // Nothing was revoked: the row is live and dual-accept still
        // accepts the token — revocation is not a cross-client kill.
        $row = Passport::token()->newQuery()->find($jti);
        $this->assertNotNull($row);
        $this->assertFalse((bool) $row->getAttribute('revoked'));
        $this->callWith($tokens['access_token'], (string) $tenant->getKey())->assertOk();

        // The refusal executed no kill, so it chains no kill event.
        $this->assertNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('actor_client_id', (string) $foreign->getKey())
            ->first());
    }

    public function test_a_foreign_client_cannot_revoke_another_clients_refresh_token(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$owner, $tokens] = $this->userTokensFor($user);
        $foreign = $this->serviceClient();
        auth()->logout();

        $this->revoke((string) $foreign->getKey(), 'nine-c-service-secret', $tokens['refresh_token'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        // The lineage still rotates for its owner — the refusal
        // consumed nothing and killed nothing.
        $this->refresh($owner, $tokens['refresh_token'])->assertOk();
    }

    // ---- C2: deactivation bulk trigger + the session-shaped gate -----

    public function test_deactivation_kills_tokens_sessions_and_chains(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:user:manage']);
        [$target] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->userTokensFor($target);

        DB::connection(config('database.default'))->table('sessions')->insert([
            'id' => 'deactivate-probe-session',
            'user_id' => $target->getAuthIdentifier(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'probe',
            'payload' => 'x',
            'last_activity' => time(),
        ]);
        auth()->logout();

        $this->stepUp($issuer);
        $this->actingAs($issuer)->postJson(
            "/api/v1/platform/users/{$target->getAuthIdentifier()}:deactivate",
            ['tenant_id' => $tid],
            $this->tenantHeaders($tenant)
        )->assertOk()->assertJsonPath('data.status', 'deactivated');

        // SEC-015 §8.1: deactivation is a bulk trigger exactly like
        // suspension — all tokens...
        $this->assertSame(0, Passport::token()->newQuery()
            ->where('user_id', $target->getAuthIdentifier())->where('revoked', false)->count());

        // ...all web sessions...
        $this->assertSame(0, DB::connection(config('database.default'))
            ->table('sessions')->where('user_id', $target->getAuthIdentifier())->count());

        // ...and the refresh lineage reads as dead.
        $this->refresh($client, $tokens['refresh_token'])->assertStatus(400);

        // The sweep is chained (D5) with its trigger reason.
        $event = AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.revoked')
            ->where('reason_code', 'user_deactivated')
            ->where('subject_ref', AuthEventRecorder::ref('sub', (string) $target->getAuthIdentifier()))
            ->first();
        $this->assertNotNull($event, 'the deactivation sweep must be hash-chained');
        $this->assertSame('allow', $event->getAttribute('decision'));
    }

    public function test_session_shaped_credentials_gate_lifecycle_at_the_enforcement_point(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $this->userTokensFor($user);

        // Session-shaped request as an ACTIVE user: allowed (control —
        // proves the 401 below comes from the gate, not from a dead
        // session fixture).
        $this->getJson('/api/v1/catalog/items', $this->tenantHeaders($tenant))->assertOk();

        // Direct status flip: NO revoke cascade runs (the endpoint is
        // deliberately bypassed) — token rows and the guard principal
        // both stay live.
        $user->forceFill(['status' => 'deactivated'])->save();

        // The enforcement point itself refuses the inactive principal:
        // fail closed even where no sweep ever happened.
        $this->getJson('/api/v1/catalog/items', $this->tenantHeaders($tenant))->assertUnauthorized();
    }

    // ---- C3/C4: console revocation sites mirror the HTTP contract ----

    public function test_console_client_revoke_invalidates_the_status_cache_immediately(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        [$user] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->userTokensFor($user);
        $jti = $this->jti($tokens['access_token']);
        auth()->logout();

        // Warm the positive verdict (authz:tok:{jti} caches the accept).
        $this->callWith($tokens['access_token'], $tid)->assertOk();
        $this->assertTrue(
            \Illuminate\Support\Facades\Cache::has('authz:tok:'.$jti),
            'the warm request must cache a positive verdict'
        );
        auth()->logout(); // fresh-process simulation (house convention)

        Artisan::call('platform:client:revoke', ['id' => (string) $client->getKey()]);

        // The console site forgot the exact entry the warm created —
        // no Cache::flush, no TTL wait (first-party sites promise 0 s;
        // a surviving entry would still answer 200 below).
        $this->assertFalse(
            \Illuminate\Support\Facades\Cache::has('authz:tok:'.$jti),
            'the console site must invalidate the shared store like the HTTP mirror'
        );

        // The very next request denies: miss → authoritative read → revoked.
        $this->callWith($tokens['access_token'], $tid)->assertUnauthorized();

        // The sweep is chained with its trigger and the pseudonymous jti.
        $event = AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.revoked')
            ->where('reason_code', 'client_revoked')
            ->where('actor_client_id', (string) $client->getKey())
            ->where('token_jti_ref', AuthEventRecorder::ref('jti', $jti))
            ->first();
        $this->assertNotNull($event, 'the console client-revoke sweep must be hash-chained');
    }

    public function test_console_secret_rotation_invalidates_the_status_cache_immediately(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->userTokensFor($user);
        $jti = $this->jti($tokens['access_token']);

        $cache = app(RevocationStatusCache::class);
        $this->assertIsArray($cache->validToken($jti)); // warm: live + cached

        Artisan::call('platform:client:rotate-secret', ['id' => (string) $client->getKey()]);

        // Without invalidation the stale positive verdict would still
        // answer for up to 30 s — first-party sites promise 0 s.
        $this->assertNull($cache->validToken($jti));

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.revoked')
            ->where('reason_code', 'secret_rotated')
            ->where('actor_client_id', (string) $client->getKey())
            ->first(), 'the rotation sweep must be hash-chained');
    }

    public function test_rfc7009_revocation_records_a_chained_event_with_the_pseudonymous_jti(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->userTokensFor($user);
        $jti = $this->jti($tokens['access_token']);
        auth()->logout();

        // Owner credentials revoke their own token (the ratified path).
        $this->revoke((string) $client->getKey(), self::CLIENT_SECRET, $tokens['access_token'])->assertOk();

        $event = AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.revoked')
            ->where('decision', 'allow')
            ->where('reason_code', 'token_revoked')
            ->where('actor_client_id', (string) $client->getKey())
            ->where('subject_ref', AuthEventRecorder::ref('sub', (string) $user->getAuthIdentifier()))
            ->where('token_jti_ref', AuthEventRecorder::ref('jti', $jti))
            ->first();

        $this->assertNotNull($event, 'an executed RFC 7009 revocation must land on the D5 chain');

        // The chain carries the kinded HMAC ref, never the raw jti.
        $this->assertStringNotContainsString($jti, (string) $event->getAttribute('token_jti_ref'));
    }

    // ---- C5: membership deactivation kills that tenant's lineages -----

    public function test_membership_deactivation_revokes_only_that_tenants_lineages(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        $tidA = (string) $tenantA->getKey();

        $user = User::factory()->create(['phone' => '+911234567890']);
        $user->forceFill([
            'email' => '911234567890@phone.local',
            'external_id' => 'cust_'.strtolower((string) Str::ulid()),
        ])->save();
        $this->makeMembership($tenantA, $user);

        $this->resolveContext($tenantA);
        try {
            BuyerProfile::factory()->create(['user_id' => $user->id]);
        } finally {
            $this->clearContext();
        }

        // One lineage per tenant: the member's tokens through tenant A's
        // client AND through tenant B's client (same user, same jti
        // space — only the client profile's tenant binding differs).
        $clientA = $this->oauthWebClient('Tenant A web client', ['tenant_id' => $tidA]);
        $clientB = $this->oauthWebClient('Tenant B web client', ['tenant_id' => (string) $tenantB->getKey()]);
        [, $tokensA] = $this->userTokensFor($user, $clientA);
        [, $tokensB] = $this->userTokensFor($user, $clientB);
        $jtiA = $this->jti($tokensA['access_token']);
        $jtiB = $this->jti($tokensB['access_token']);

        // Buyer self-deactivation (OTP stepped up, the profile-surface
        // contract) suspends the membership in tenant A.
        $challenge = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tidA,
            'phone' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');

        $this->actingAs($user)->postJson('/api/v1/customers/profile:deactivate', [
            'challenge_id' => (string) $challenge,
            'token' => $this->msg91TokenFor('+911234567890'),
        ], $this->tenantHeaders($tenantA))->assertOk()->assertJsonPath('data.status', 'deactivated');

        // Tenant A's lineage dies with the membership (access row and
        // its refresh half)...
        $rowA = Passport::token()->newQuery()->find($jtiA);
        $this->assertNotNull($rowA);
        $this->assertTrue((bool) $rowA->getAttribute('revoked'));
        $this->assertSame(0, Passport::refreshToken()->newQuery()
            ->where('access_token_id', $jtiA)->where('revoked', false)->count());

        // ...tenant B's lineage is a different membership's concern and
        // must survive untouched.
        $rowB = Passport::token()->newQuery()->find($jtiB);
        $this->assertNotNull($rowB);
        $this->assertFalse((bool) $rowB->getAttribute('revoked'));

        // The kill is chained with its trigger and tenant.
        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.revoked')
            ->where('reason_code', 'membership_revoked')
            ->where('tenant_ref', $tidA)
            ->where('subject_ref', AuthEventRecorder::ref('sub', (string) $user->getAuthIdentifier()))
            ->first(), 'the membership lineage kill must be chained with its tenant');
    }

    // ---- C6: introspection fails closed -------------------------------

    public function test_forged_signature_introspects_inactive_while_the_genuine_token_stays_active(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $tokens] = $this->userTokensFor($user);
        $service = $this->serviceClient();
        auth()->logout();

        // Real jti, broken signature: a flipped first signature
        // character cannot verify.
        $segments = explode('.', $tokens['access_token']);
        $this->assertCount(3, $segments);
        $first = substr($segments[2], 0, 1);
        $forged = $segments[0].'.'.($segments[1]).'.'.($first === 'A' ? 'B' : 'A').substr($segments[2], 1);

        $this->introspect($service, $forged)->assertOk()->assertJsonPath('active', false);

        // Control: the untouched original still introspects active — the
        // denial comes from the signature check, not general breakage.
        $this->introspect($service, $tokens['access_token'])->assertOk()->assertJsonPath('active', true);
    }

    public function test_introspection_denies_a_suspended_owners_live_token(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $tokens] = $this->userTokensFor($user);
        $service = $this->serviceClient();
        auth()->logout();

        $this->introspect($service, $tokens['access_token'])->assertOk()->assertJsonPath('active', true);

        // Direct status flip: rows stay live, but the audience would
        // refuse the principal — introspection must agree with
        // dual-accept instead of advertising a credential that dies.
        $user->forceFill(['status' => 'suspended'])->save();

        $this->introspect($service, $tokens['access_token'])->assertOk()->assertJsonPath('active', false);
    }

    public function test_a_token_row_without_expiry_introspects_inactive(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $tokens] = $this->userTokensFor($user);
        $service = $this->serviceClient();
        $jti = $this->jti($tokens['access_token']);
        auth()->logout();

        $row = Passport::token()->newQuery()->find($jti);
        $this->assertNotNull($row);
        $row->forceFill(['expires_at' => null])->save();

        // No bounded lifetime ⇒ no active answer (fail closed, never
        // "valid forever").
        $this->introspect($service, $tokens['access_token'])->assertOk()->assertJsonPath('active', false);
    }

    // ---- helpers -------------------------------------------------------

    /**
     * The decoded jti claim behind an issued JWT access token — the
     * authority for locating the row, never for trusting the token.
     */
    private function jti(string $accessToken): string
    {
        $segments = explode('.', $accessToken);
        $this->assertCount(3, $segments);
        $claims = json_decode(base64_decode(strtr($segments[1], '-_', '+/')), true);
        $this->assertIsArray($claims);
        $this->assertNotEmpty($claims['jti'] ?? null);

        return (string) $claims['jti'];
    }

    /**
     * A confidential first-party service client (the introspection
     * audience shape; any confidential client can authenticate a
     * revocation request).
     */
    private function serviceClient(): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => '9c service client',
            'secret' => 'nine-c-service-secret',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
            'allowed_grants' => ['client_credentials'],
        ]);

        return $client;
    }

    /**
     * @return TestResponse
     */
    private function revoke(string $clientId, string $clientSecret, string $token): TestResponse
    {
        return $this->postJson('/api/oauth/revoke', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'token' => $token,
        ]);
    }

    /**
     * @return TestResponse
     */
    private function introspect(Client $client, string $token): TestResponse
    {
        return $this->postJson('/api/oauth/introspect', [
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'nine-c-service-secret',
            'token' => $token,
        ]);
    }

    /**
     * @return TestResponse
     */
    private function refresh(Client $client, string $refreshToken): TestResponse
    {
        return $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ]);
    }

    /**
     * Ordinary dual-accept API call (bearer shaped, session cleared by
     * callers beforehand).
     *
     * @return TestResponse
     */
    private function callWith(string $accessToken, string $tid): TestResponse
    {
        return $this->getJson('/api/v1/catalog/items', [
            'Authorization' => 'Bearer '.$accessToken,
            'X-Tenant-Id' => $tid,
        ]);
    }
}
