<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\OidcBackchannelDelivery;
use App\Platform\Identity\OAuth\OidcSessionBinding;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Client;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * A-SEC-004-5: OIDC Back-Channel Logout delivery — bindings recorded
 * at authorization (one row per sid+client, upserted), app logout
 * pushing a spec-shaped logout_token to the registered endpoint with
 * the delivery persisted + chained, failures parking on a bounded
 * backoff for auth:retry-backchannel-logout (which re-mints a FRESH
 * token per attempt — never a stored credential), terminal at
 * MAX_ATTEMPTS, and clients without an endpoint failing closed to
 * "marked notified, zero outbound calls".
 */
final class BackchannelLogoutTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;
    use IssuesUserTokens;

    private const HOST = 'http://auth.localhost';

    private const BACKCHANNEL = 'https://rp.test/backchannel';

    private const SID = '01JBACKCHANNELTESTSESSION00001';

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

    private function oidcClient(bool $withBackchannel = true): Client
    {
        $profile = ['allowed_scopes' => ['openid']];

        if ($withBackchannel) {
            $profile['backchannel_logout_uri'] = self::BACKCHANNEL;
        }

        return $this->oauthWebClient('OIDC backchannel client', $profile);
    }

    /**
     * @return array<string, mixed>
     */
    private function authorizeParams(Client $client): array
    {
        return [
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
            'nonce' => 'backchannel-nonce-1',
        ];
    }

    /**
     * Authorize + consent (openid, seeded ceremony): the point where
     * the session<->client binding is written. Returns the consent
     * redirect; no exchange — bindings ride the authorization, not
     * the token.
     */
    private function authorizeAndApprove(Client $client, User $user): void
    {
        $this->withSession(['oidc.ceremony' => [
            'auth_time' => time(),
            'amr' => ['otp'],
            'acr' => 'aal1',
            'sid' => self::SID,
        ]]);

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client)
        ))->assertOk();

        $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ])->assertStatus(302);
    }

    private function logout(): void
    {
        $this->post(self::HOST.'/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.signed_out', true);
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

    public function test_authorization_records_one_binding_per_session_and_client(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->authorizeAndApprove($client, $user);

        $bindings = OidcSessionBinding::query()
            ->where('sid', self::SID)
            ->where('client_id', (string) $client->getKey())
            ->get();

        $this->assertCount(1, $bindings);
        $this->assertSame((int) $user->getAuthIdentifier(), (int) $bindings[0]->user_id);
        $this->assertNull($bindings[0]->notified_at);
        $this->assertTrue($bindings[0]->expires_at->isFuture());

        // A second authorization from the same session upserts the
        // same row — the enumeration never duplicates an RP.
        $this->authorizeAndApprove($client, $user);

        $this->assertSame(1, OidcSessionBinding::query()
            ->where('sid', self::SID)
            ->where('client_id', (string) $client->getKey())
            ->count());
    }

    public function test_app_logout_pushes_a_logout_token_and_persists_the_delivery(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $this->authorizeAndApprove($client, $user);

        Http::fake();
        $this->logout();

        $captured = null;

        Http::assertSent(function ($request) use (&$captured): bool {
            if ($request->url() !== self::BACKCHANNEL) {
                return false;
            }

            $captured = (string) $request['logout_token'];

            return $request->isForm();
        });

        $this->assertIsString($captured);

        [$header, $claims] = $this->decodeJwt($captured);

        $this->assertSame('logout+jwt', $header['typ']);
        $this->assertSame('RS256', $header['alg']);
        $this->assertNotEmpty($header['kid']);
        $this->assertSame('http://auth.localhost', $claims['iss']);
        $this->assertSame((string) $client->getKey(), $claims['aud']);
        $this->assertSame(self::SID, $claims['sid']);
        $this->assertArrayHasKey('http://schemas.openid.net/event/backchannel-logout', $claims['events']);
        $this->assertSame(120, $claims['exp'] - $claims['iat']);
        $this->assertNotEmpty($claims['jti']);

        // Row lifecycle: one delivered attempt, binding notified.
        $delivery = OidcBackchannelDelivery::query()->where('sid', self::SID)->firstOrFail();
        $this->assertSame(OidcBackchannelDelivery::STATE_DELIVERED, $delivery->state);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertNull($delivery->last_error);

        $binding = OidcSessionBinding::query()->where('sid', self::SID)->firstOrFail();
        $this->assertNotNull($binding->notified_at);

        // Chain: shared-terminator logout row + delivery outcome row.
        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.logout')
            ->where('decision', 'allow')
            ->where('reason_code', 'app_logout')
            ->where('subject_ref', AuthEventRecorder::ref('sub', (string) $user->getAuthIdentifier()))
            ->count());
        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.backchannel_logout')
            ->where('decision', 'allow')
            ->where('reason_code', 'delivered')
            ->where('session_ref', AuthEventRecorder::ref('sess', self::SID))
            ->count());

        // The OP session itself is gone.
        $this->assertNull(session('oidc.ceremony'));
    }

    public function test_failed_delivery_parks_and_the_retry_command_delivers(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $this->authorizeAndApprove($client, $user);

        Http::fake([
            self::BACKCHANNEL => Http::sequence()
                ->push('', 500)
                ->push('', 204),
        ]);

        $this->logout();

        // Inline attempt failed: parked, bounded, visible.
        $delivery = OidcBackchannelDelivery::query()->where('sid', self::SID)->firstOrFail();
        $this->assertSame(OidcBackchannelDelivery::STATE_PENDING, $delivery->state);
        $this->assertSame(1, $delivery->attempts);
        $this->assertTrue($delivery->next_attempt_at->isFuture());
        $this->assertStringContainsString('500', (string) $delivery->last_error);

        $this->artisan('auth:retry-backchannel-logout', ['--force' => true])
            ->assertExitCode(0);

        $delivery->refresh();
        $this->assertSame(OidcBackchannelDelivery::STATE_DELIVERED, $delivery->state);
        $this->assertSame(2, $delivery->attempts);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertNull($delivery->last_error);

        // The chain saw both outcomes (error then allow).
        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.backchannel_logout')
            ->where('decision', 'error')
            ->where('reason_code', 'transport_error')
            ->count());
        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.backchannel_logout')
            ->where('decision', 'allow')
            ->where('reason_code', 'delivered')
            ->count());
    }

    public function test_each_attempt_re_mints_a_fresh_token_never_a_stored_one(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $this->authorizeAndApprove($client, $user);

        Http::fake([
            self::BACKCHANNEL => Http::sequence()
                ->push('', 500)
                ->push('', 204),
        ]);

        $this->logout();
        $this->artisan('auth:retry-backchannel-logout', ['--force' => true])
            ->assertExitCode(0);

        $tokens = [];

        Http::assertSent(function ($request) use (&$tokens): bool {
            if ($request->url() !== self::BACKCHANNEL) {
                return false;
            }

            $tokens[] = (string) $request['logout_token'];

            return true;
        });

        $this->assertCount(2, $tokens);

        [, $first] = $this->decodeJwt($tokens[0]);
        [, $second] = $this->decodeJwt($tokens[1]);

        // Same session, two attempts, two DIFFERENT tokens: the retry
        // minted through the custody again (jti freshness proves the
        // credential was never persisted on the delivery row).
        $this->assertSame(self::SID, $first['sid']);
        $this->assertSame(self::SID, $second['sid']);
        $this->assertNotSame($first['jti'], $second['jti']);
        $this->assertSame('http://auth.localhost', $second['iss']);
    }

    public function test_attempts_exhaust_to_a_terminal_failed_row(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $this->authorizeAndApprove($client, $user);

        Http::fake([self::BACKCHANNEL => Http::response('', 500)]);

        $this->logout();

        // Inline attempt 1 … then four forced retries reach the cap.
        foreach (range(2, 5) as $attempt) {
            $this->artisan('auth:retry-backchannel-logout', ['--force' => true])
                ->assertExitCode(0);

            $delivery = OidcBackchannelDelivery::query()->where('sid', self::SID)->firstOrFail();
            $this->assertSame($attempt, $delivery->attempts);
        }

        $delivery = OidcBackchannelDelivery::query()->where('sid', self::SID)->firstOrFail();
        $this->assertSame(OidcBackchannelDelivery::STATE_FAILED, $delivery->state);
        $this->assertNotNull($delivery->last_error);

        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.backchannel_logout')
            ->where('reason_code', 'attempts_exhausted')
            ->count());
    }

    public function test_client_without_an_endpoint_is_marked_notified_with_zero_outbound_calls(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient(withBackchannel: false);
        $this->authorizeAndApprove($client, $user);

        Http::fake();
        $this->logout();

        Http::assertNothingSent();

        $binding = OidcSessionBinding::query()->where('sid', self::SID)->firstOrFail();
        $this->assertNotNull($binding->notified_at);
        $this->assertSame(0, OidcBackchannelDelivery::query()->where('sid', self::SID)->count());
    }
}
