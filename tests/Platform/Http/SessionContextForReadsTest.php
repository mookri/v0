<?php

namespace Tests\Platform\Http;

use App\Models\User;
use Tests\TenantIntegrationTestCase;

/**
 * Session context for API reads (doc 06 §8.4): the admin shell's
 * interactive components fetch module JSON with the web session's user.
 *
 * The security posture pinned here is the point of the surface:
 * safe methods with a real browser cookie authenticate against module
 * routes, unsafe methods never do (mutations stay token-only, so the
 * CSRF posture of the API does not move), cookie-less clients are
 * byte-identical to before, and one cookie keeps working across the
 * web and API surfaces in both directions.
 */
final class SessionContextForReadsTest extends TenantIntegrationTestCase
{
    /**
     * @return array{string, string} cookie name and wire value
     */
    private function loginFor(User $user): array
    {
        // Persistent driver: the array driver is not session-persistent
        // and never issues a cookie to replay.
        config(['session.driver' => 'database']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticated();

        $name = (string) config('session.cookie');
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                // Web-group cookie: encrypted on the wire, replayed verbatim.
                $value = $cookie->getValue();

                $this->resetAuthState();

                // JSON requests only carry cookies for credentialed (fetch
                // same-origin) calls, like the admin shell's.
                $this->withCredentials();

                return [$name, $value];
            }
        }

        $this->fail('login did not issue a session cookie');
    }

    /**
     * Drop the guard's in-memory user and the resolved session singletons so
     * every later request in this test starts from clean process state and
     * proves the cookie, not the test's login call. StartSession is pinned to
     * the manager it was first resolved with, so it must be forgotten too or
     * it would keep driving a stale store.
     */
    private function resetAuthState(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('session');
        $this->app->forgetInstance('session.store');
        $this->app->forgetInstance(\Illuminate\Session\Middleware\StartSession::class);
    }

    public function test_session_cookie_authenticates_module_reads(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        [$name, $value] = $this->loginFor($user);

        $this->withUnencryptedCookie($name, $value)
            ->getJson('/api/v1/pandit/profiles', $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['request_id', 'page']]);
    }

    public function test_session_cookie_never_authenticates_module_writes(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        [$name, $value] = $this->loginFor($user);

        // Same cookie, unsafe method: no session starts, no CSRF surface.
        $this->withUnencryptedCookie($name, $value)
            ->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $this->tenantHeaders($tenant))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_missing_membership_is_denied_for_session_reads(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create(); // no membership on purpose

        [$name, $value] = $this->loginFor($user);

        $this->withUnencryptedCookie($name, $value)
            ->getJson('/api/v1/pandit/profiles', $this->tenantHeaders($tenant))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'membership_inactive');
    }

    public function test_api_response_cookie_keeps_the_web_session_alive(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        [$name, $value] = $this->loginFor($user);

        // Hop through the API with the web cookie…
        $api = $this->withUnencryptedCookie($name, $value)
            ->getJson('/api/v1/pandit/profiles', $this->tenantHeaders($tenant))
            ->assertOk();

        // …take the cookie the API returned and prove the web surface
        // still recognizes it (the API re-encrypts what it re-issues).
        $apiValue = null;
        foreach ($api->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                $apiValue = $cookie->getValue();
            }
        }
        $this->assertIsString($apiValue);
        $this->assertNotSame($value, $apiValue);

        $this->resetAuthState();

        $this->withUnencryptedCookie($name, $apiValue)
            ->get('/')
            ->assertRedirect('/dashboards/analytics');
    }
}
