<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\Models\AuthEvent;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Testing\TestResponse;
use OTPHP\TOTP;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * ADR-0020 Phase 5: the unified login surface on the auth subdomain.
 * One ceremony — phone OTP (MSG91 widget JWT redeemed server-side) or
 * email+password — auto-provisions on verified proof and lands a
 * web-guard session, from which Passport's authorize/consent resumes
 * the OAuth flow. Host-gated, MFA-parity, no open redirects, every
 * allow/deny hash-chained.
 */
final class FederationLoginTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;
    use IssuesUserTokens;

    private const HOST = 'http://auth.localhost';

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
     * @return array{0: User, 1: string|null}
     */
    private function createUser(string $phone, bool $mfa = false, string $password = 'secret'): array
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Federation User',
            'email' => preg_replace('/[^0-9]/', '', $phone).'@test.local',
            'phone' => $phone,
            'phone_verified_at' => now(),
            'external_id' => 'cust_test_'.bin2hex(random_bytes(8)),
            'password' => bcrypt($password),
            'mfa_enabled' => $mfa,
        ]);

        $secret = null;
        if ($mfa) {
            $totp = TOTP::generate();
            $secret = $totp->getSecret();
            $user->mfa_secret = Crypt::encryptString($secret);
        }

        $user->save();

        return [$user, $secret];
    }

    private function startOtp(string $phone): string
    {
        $res = $this->postJson(self::HOST.'/auth/login/otp', ['phone' => $phone]);
        $res->assertStatus(202);

        return (string) $res->json('data.challenge_id');
    }

    private function loginViaOtp(string $phone, ?string $next = null): TestResponse
    {
        $body = [
            'challenge_id' => $this->startOtp($phone),
            'token' => $this->msg91TokenFor($phone),
        ];

        if ($next !== null) {
            $body['next'] = $next;
        }

        return $this->postJson(self::HOST.'/auth/login/verify', $body);
    }

    public function test_login_page_renders_on_the_federation_host_only(): void
    {
        $this->get(self::HOST.'/auth/login')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('csrf-token');

        // Same path on the business host stays invisible (no oracle).
        $this->get('http://localhost/auth/login')->assertNotFound();

        // Direct-mode default: the surface does not exist anywhere.
        config(['federation.domain' => '']);
        $this->get(self::HOST.'/auth/login')->assertNotFound();
    }

    public function test_ceremony_endpoints_fail_closed_off_domain(): void
    {
        $this->postJson('http://localhost/auth/login/otp', ['phone' => '9898989898'])
            ->assertNotFound();

        config(['federation.domain' => '']);
        $this->postJson(self::HOST.'/auth/login/verify', ['challenge_id' => 'x', 'token' => 'y'])
            ->assertNotFound();
        $this->postJson(self::HOST.'/auth/login/password', ['email' => 'a@b.test', 'password' => 'x'])
            ->assertNotFound();
        $this->postJson(self::HOST.'/auth/logout')->assertNotFound();
    }

    public function test_phone_otp_login_auto_provisions_and_establishes_the_web_session(): void
    {
        $phone = '9898989898';

        $res = $this->loginViaOtp($phone);
        $res->assertOk()
            ->assertJsonPath('data.object', 'federation_session')
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.redirect', null);

        $this->assertAuthenticated();

        $user = User::query()->where('phone', $phone)->firstOrFail();
        $this->assertTrue(str_starts_with((string) $user->external_id, 'fed_'));
        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame('9898989898@phone.local', $user->email);

        // D1: the identity issuer knows accounts, not memberships —
        // federation login never creates a tenant membership.
        $this->assertDatabaseCount('tenant_memberships', 0);

        // Default landing: whoami over the established session.
        $this->get(self::HOST.'/auth/session')
            ->assertOk()
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.user.id', $user->external_id);
    }

    public function test_existing_account_is_found_by_format_variant_not_duplicated(): void
    {
        [$user] = $this->createUser('+919898989876');

        // Same number, national form: the session lands on the existing
        // account; nobody is provisioned twice.
        $this->loginViaOtp('9898989876')
            ->assertOk()
            ->assertJsonPath('data.authenticated', true);

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_verify_rejects_wrong_tokens_and_unknown_challenges_with_one_401_shape(): void
    {
        $phone = '9898989890';
        $challengeId = $this->startOtp($phone);

        // Token attesting a different number.
        $this->postJson(self::HOST.'/auth/login/verify', [
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor('919999999999'),
        ])->assertStatus(401)->assertJsonPath('error.code', 'unauthenticated');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());

        // Unknown challenge id: identical shape, no oracle.
        $this->postJson(self::HOST.'/auth/login/verify', [
            'challenge_id' => '01JZZZZZZZZZZZZZZZZZZZZZZZ',
            'token' => $this->msg91TokenFor($phone),
        ])->assertStatus(401)->assertJsonPath('error.code', 'unauthenticated');

        $this->assertGuest();
    }

    public function test_resume_target_is_returned_and_open_redirect_shapes_are_refused(): void
    {
        // A valid same-host target passes through verbatim.
        $this->loginViaOtp('9898989801', '/oauth/authorize?client_id=abc')
            ->assertOk()
            ->assertJsonPath('data.redirect', '/oauth/authorize?client_id=abc');

        // Every open-redirect shape collapses to null (no Location
        // handed out); a fresh phone per case sidesteps the
        // live-challenge gate.
        $refusals = [
            'https://evil.example/steal',
            '//evil.example/steal',
            '/\\evil.example',
            'javascript:alert(1)',
            'https://auth.localhost@evil.example/x',
        ];

        foreach ($refusals as $i => $target) {
            $this->loginViaOtp('98989898'.(string) (11 + $i), $target)
                ->assertOk()
                ->assertJsonPath('data.redirect', null);
        }
    }

    public function test_mfa_enrolled_account_parks_the_otp_then_completes_with_totp(): void
    {
        $phone = '9898989877';
        [$user, $secret] = $this->createUser($phone, mfa: true);
        $challengeId = $this->startOtp($phone);

        // Phase 1 without TOTP: parked row, NO session yet.
        $this->postJson(self::HOST.'/auth/login/verify', [
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertStatus(202)
            ->assertJsonPath('data.status', 'mfa_required')
            ->assertJsonPath('data.methods', ['totp']);
        $this->assertGuest();

        // Wrong TOTP: denied, row stays held for a retry.
        $this->postJson(self::HOST.'/auth/login/verify', [
            'challenge_id' => $challengeId,
            'totp_code' => '000000',
        ])->assertStatus(401);
        $this->assertGuest();

        // Right TOTP consumes the held row and lands the session.
        $this->postJson(self::HOST.'/auth/login/verify', [
            'challenge_id' => $challengeId,
            'totp_code' => TOTP::createFromSecret($secret)->now(),
        ])->assertOk()->assertJsonPath('data.authenticated', true);

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_login_lands_a_session_and_denies_bad_credentials(): void
    {
        [$user] = $this->createUser('9898989866', password: 'correct-horse');

        // Wrong password and unknown email share one 401 shape.
        $this->postJson(self::HOST.'/auth/login/password', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertStatus(401)->assertJsonPath('error.code', 'unauthenticated');
        $this->assertGuest();

        $this->postJson(self::HOST.'/auth/login/password', [
            'email' => 'ghost@example.test',
            'password' => 'whatever',
        ])->assertStatus(401)->assertJsonPath('error.code', 'unauthenticated');
        $this->assertGuest();

        // Correct credentials land the session.
        $this->postJson(self::HOST.'/auth/login/password', [
            'email' => $user->email,
            'password' => 'correct-horse',
        ])->assertOk()->assertJsonPath('data.authenticated', true);
        $this->assertAuthenticatedAs($user);

        // The two denies and the allow are hash-chained (D5).
        $this->assertSame(2, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.login')
            ->where('decision', 'deny')
            ->count());
        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.login')
            ->where('decision', 'allow')
            ->count());
    }

    public function test_password_mfa_parks_then_totp_completes(): void
    {
        [$user, $secret] = $this->createUser('9898989864', mfa: true, password: 'correct-horse');

        // Password alone: parked server-side, 202, no session.
        $this->postJson(self::HOST.'/auth/login/password', [
            'email' => $user->email,
            'password' => 'correct-horse',
        ])->assertStatus(202)->assertJsonPath('data.status', 'mfa_required');
        $this->assertGuest();

        // Wrong TOTP: denied, pending stays for the retry.
        $this->postJson(self::HOST.'/auth/login/totp', ['totp_code' => '000000'])
            ->assertStatus(401);
        $this->assertGuest();

        // Right TOTP lands the session.
        $this->postJson(self::HOST.'/auth/login/totp', [
            'totp_code' => TOTP::createFromSecret($secret)->now(),
        ])->assertOk()->assertJsonPath('data.authenticated', true);
        $this->assertAuthenticatedAs($user);
    }

    public function test_totp_endpoint_without_a_parked_login_is_denied(): void
    {
        $this->postJson(self::HOST.'/auth/login/totp', ['totp_code' => '123456'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');
        $this->assertGuest();
    }

    public function test_inactive_account_never_lands_a_session_on_any_path(): void
    {
        [$user] = $this->createUser('9898989865');
        $user->forceFill(['status' => 'suspended'])->save();

        $this->postJson(self::HOST.'/auth/login/password', [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertStatus(401);
        $this->assertGuest();

        $this->loginViaOtp('9898989865')->assertStatus(401);
        $this->assertGuest();
    }

    public function test_logout_invalidates_the_web_session(): void
    {
        $this->loginViaOtp('9898989899')->assertOk();
        $this->assertAuthenticated();

        $this->postJson(self::HOST.'/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.signed_out', true);
        $this->assertGuest();

        $this->get(self::HOST.'/auth/session')
            ->assertOk()
            ->assertJsonPath('data.authenticated', false);
    }

    public function test_passport_authorize_bounces_to_the_federation_login_and_resumes(): void
    {
        $client = $this->oauthWebClient();
        $params = [
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code',
            'scope' => '',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
        ];
        // Symfony's fullUrl() (what redirect()->guest() stashes as the
        // intended URL) rebuilds the query with sorted keys — build the
        // canonical form so comparisons are exact.
        ksort($params);
        $query = http_build_query($params);
        $authorizeUrl = self::HOST.'/oauth/authorize?'.$query;

        // Unauthenticated: bounced to the unified login with the
        // intended URL stashed for resume.
        $bounce = $this->get($authorizeUrl);
        $bounce->assertStatus(302);
        $this->assertStringEndsWith('/auth/login', (string) $bounce->headers->get('Location'));
        $this->assertSame($authorizeUrl, session('url.intended'));

        // The ceremony completes and hands the resume target back
        // verbatim (path + query, normalized off any authority).
        $login = $this->loginViaOtp('9898989842');
        $login->assertOk()->assertJsonPath('data.redirect', '/oauth/authorize?'.$query);

        // Resume: the web session satisfies Passport's guard — the
        // consent screen renders.
        $this->get(self::HOST.$login->json('data.redirect'))->assertOk();
    }

    public function test_probe_publishes_the_configured_auth_mode(): void
    {
        $this->get(self::HOST.'/auth/health')
            ->assertOk()
            ->assertJsonPath('mode', 'federated');
    }

    public function test_invalid_auth_mode_fails_closed(): void
    {
        config(['federation.mode' => 'halfway']);

        $this->expectException(\App\Platform\Identity\Federation\FederationConfigurationException::class);
        \App\Platform\Identity\Federation\FederationMode::current();
    }

    public function test_federated_mode_requires_domain_and_issuer(): void
    {
        config(['federation.mode' => 'federated', 'federation.domain' => '', 'federation.issuer' => '']);

        $this->expectException(\App\Platform\Identity\Federation\FederationConfigurationException::class);
        \App\Platform\Identity\Federation\FederationMode::current();
    }
}
