<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\ActorTokenIssuer;
use App\Platform\Identity\ActorTokenVerifier;
use Tests\TenantIntegrationTestCase;

/**
 * Identity self-service (api-platform platform surfaces): own profile
 * read/update with strict unknown-field rejection, web-session inventory,
 * and remote-session revocation that spares the current session.
 */
final class IdentitySelfServiceTest extends TenantIntegrationTestCase
{
    public function test_guests_are_denied_everywhere(): void
    {
        $this->getJson('/api/v1/identity/profile')->assertUnauthorized();
        $this->patchJson('/api/v1/identity/profile', ['name' => 'X'])->assertUnauthorized();
        $this->getJson('/api/v1/identity/sessions')->assertUnauthorized();
        $this->postJson('/api/v1/identity/sessions:revoke-others')->assertUnauthorized();
    }

    public function test_profile_read_and_strict_update(): void
    {
        $user = User::factory()->create(['name' => 'Asha', 'email_verified_at' => now()]);

        $this->actingAs($user)->getJson('/api/v1/identity/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Asha')
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonStructure(['data', 'meta' => ['request_id']]);

        $this->actingAs($user)->patchJson('/api/v1/identity/profile', ['name' => 'Asha Devi', 'role' => 'admin'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->actingAs($user)->patchJson('/api/v1/identity/profile', ['name' => 'Asha Devi'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Asha Devi');

        $this->assertSame('Asha Devi', $user->fresh()->name);
    }

    private function sessionCookieName(): string
    {
        return (string) config('session.cookie');
    }

    public function test_sessions_inventory_and_remote_revoke(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();

        // Warm-up persists a session row; reuse its cookie so the suite
        // holds one stable session across requests.
        $warm = $this->actingAs($user)->getJson('/api/v1/identity/sessions')->assertOk();
        $sessionId = $warm->headers->getCookies()[0]->getValue();
        // withUnencryptedCookie: api identity routes carry no
        // EncryptCookies middleware (documented trade-off, TLS-only);
        // withCookie would encrypt and the server would mint anew.
        $withSession = function () use ($user, $sessionId) {
            $this->actingAs($user)->withUnencryptedCookie($this->sessionCookieName(), $sessionId);
            return $this->withCredentials()->getJson('/api/v1/identity/sessions');
        };
        $withSessionPost = function () use ($user, $sessionId) {
            $this->actingAs($user)->withUnencryptedCookie($this->sessionCookieName(), $sessionId);
            return $this->withCredentials()->postJson('/api/v1/identity/sessions:revoke-others');
        };

        $first = $withSession();
        $this->assertCount(1, $first->json('data'));
        $this->assertTrue($first->json('data.0.current'));

        // Simulate a second device by inserting a session row directly.
        \Illuminate\Support\Facades\DB::connection(config('database.default'))->table('sessions')->insert([
            'id' => str_repeat('b', 40),
            'user_id' => $user->id,
            'ip_address' => '203.0.113.7',
            'user_agent' => 'OtherDevice/1.0',
            'payload' => 'x',
            'last_activity' => time(),
        ]);

        $listed = $withSession();
        $this->assertCount(2, $listed->json('data'));

        $revoked = $withSessionPost();
        $revoked->assertOk();
        $revoked->assertJsonPath('data.revoked', 1);

        $after = $withSession();
        $this->assertCount(1, $after->json('data'));
        $this->assertTrue($after->json('data.0.current'));
    }

    public function test_revoke_without_session_cookie_reports_zero(): void
    {
        // Token-style clients carry no session cookie: StartSession mints an
        // anonymous one, so revoke correctly reports zero others rather than
        // erroring — the guard documents the degraded mode.
        config(['session.driver' => 'database']);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/identity/sessions:revoke-others')
            ->assertOk()
            ->assertJsonPath('data.revoked', 0);
    }

    public function test_bearer_path_revoke_others_spares_the_presented_session(): void
    {
        // F-SMOKE3: the BFF authenticates with a bearer and no session
        // cookie, so StartSession mints an ad-hoc web-session id that
        // matches no row — the web-row exclusion is a no-op there, and the
        // actor sweep used to revoke the caller's OWN jti, logging it out
        // of the very request that asked to end *other* sessions. The
        // verified actor AuthenticateAny stamps on the request is the
        // session that must survive (same attribute logout reads).
        config(['session.driver' => 'database']);
        $secret = 'revoke-others-bearer-test-secret';
        config()->set('identity.hmac_secret', $secret);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill(['external_id' => 'ext_revoke_others_bearer'])->save();

        $now = gmdate('Y-m-d\TH:i:s.v\Z');
        $presented = ActorTokenIssuer::mint($secret, 'ext_revoke_others_bearer', 86400, $now);
        $other = ActorTokenIssuer::mint($secret, 'ext_revoke_others_bearer', 86400, $now);

        // A second device's web session: the sweep must still count it.
        \Illuminate\Support\Facades\DB::connection(config('database.default'))->table('sessions')->insert([
            'id' => str_repeat('c', 40),
            'user_id' => $user->id,
            'ip_address' => '203.0.113.9',
            'user_agent' => 'OtherDevice/1.0',
            'payload' => 'x',
            'last_activity' => time(),
        ]);

        $this->postJson('/api/v1/identity/sessions:revoke-others', [], [
            'Authorization' => 'Bearer '.$presented['token'],
        ])->assertOk()->assertJsonPath('data.revoked', 1);
        auth()->logout();

        // The presented session survives (contract: end every OTHER one)…
        $this->assertNotNull(
            ActorTokenVerifier::verify($secret, $presented['token'], gmdate('Y-m-d\TH:i:s.v\Z')),
            'The presented actor session must survive its own revoke-others call.'
        );
        // …while every other actor session dies with the call.
        $this->assertNull(
            ActorTokenVerifier::verify($secret, $other['token'], gmdate('Y-m-d\TH:i:s.v\Z')),
            'A non-presented actor session must be revoked.'
        );
    }

    public function test_users_only_ever_see_themselves(): void
    {
        $me = User::factory()->create(['email' => 'me@example.com']);
        User::factory()->create(['email' => 'other@example.com']);

        $this->actingAs($me)->getJson('/api/v1/identity/profile')->assertJsonPath('data.email', 'me@example.com');
    }
}
