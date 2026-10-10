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
        // This test uses bearer tokens (which create actor_sessions) because
        // the sessions() endpoint now reads from actor_sessions (bearer
        // devices) rather than the web sessions table.
        config(['session.driver' => 'database']);
        $secret = 'sessions-inventory-test-secret';
        config()->set('identity.hmac_secret', $secret);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill(['external_id' => 'ext_sessions_inventory'])->save();

        // Create two actor_sessions for the same user (simulating two devices)
        $now = gmdate('Y-m-d\TH:i:s.v\Z');
        $first = ActorTokenIssuer::mint($secret, 'ext_sessions_inventory', 86400, $now, [], null, null, 'Chrome/120.0.0.0', '203.0.113.5');
        $second = ActorTokenIssuer::mint($secret, 'ext_sessions_inventory', 86400, $now, [], null, null, 'Firefox/115.0', '198.51.100.10');

        // Debug: check if actor_sessions were created
        $count = \Illuminate\Support\Facades\DB::connection('central')->table('actor_sessions')
            ->whereIn('id', [$first['session_id'], $second['session_id']])
            ->count();
        $this->assertEquals(2, $count, 'Expected 2 actor_sessions to be created');

        // Call sessions() with the first token (bearer auth) - use postJson like test_bearer
        $headers = ['Authorization' => 'Bearer '.$first['token']];
        $listed = $this->getJson('/api/v1/identity/sessions', $headers);
        
        // Debug: dump response if it fails
        if ($listed->status() !== 200) {
            $this->fail('sessions() returned '.$listed->status().': '.json_encode($listed->json()));
        }
        $listed->assertOk();
        $this->assertCount(2, $listed->json('data'));
        $this->assertTrue($listed->json('data.0.current'), 'First token session should be marked current');
        $this->assertFalse($listed->json('data.1.current'));

        // Revoke the other session (second device)
        $revoked = $this->postJson('/api/v1/identity/sessions:revoke-others', [], ['Authorization' => 'Bearer '.$first['token']])->assertOk();
        $revoked->assertJsonPath('data.revoked', 1);

        // First session should survive, second should be revoked
        $after = $this->getJson('/api/v1/identity/sessions', ['Authorization' => 'Bearer '.$first['token']])->assertOk();
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
