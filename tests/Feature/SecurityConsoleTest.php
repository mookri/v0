<?php

namespace Tests\Feature;

use OTPHP\TOTP;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Security settings console page (plan slice 9, doc 06 §7 + §7.5):
 * /account/security — profile, TOTP second factor, and web sessions —
 * with its forms posting the SHARED identity self-service actions
 * (web → redirect + coded flash; one-time setup key and recovery codes
 * ride the flash and die with that single render). API envelopes stay
 * byte-identical: MfaTest + the identity self-service tests are the
 * parity pin.
 */
final class SecurityConsoleTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    private const PAGE = 'http://localhost/account/security';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
    }

    private function totpFor(string $secret): string
    {
        return TOTP::createFromSecret($secret)->now();
    }

    /** The setup key is the only long base32 run in the enroll flash. */
    private function setupKeyFromFlash(): string
    {
        preg_match('/\b[A-Z2-7]{16,}\b/', (string) session('flash'), $matches);

        return (string) ($matches[0] ?? '');
    }

    public function test_security_page_requires_authentication(): void
    {
        $this->get(self::PAGE)->assertRedirect('/login');
    }

    public function test_security_page_renders_with_mfa_disabled_and_profile_intact(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->get('/account/security')
            ->assertOk()
            ->assertSee('id="profile-form"', false)
            ->assertSee('id="mfa-stepup-form"', false)
            ->assertSee('id="mfa-revoke-form"', false)
            ->assertSee('id="session-rows"', false)
            // Topbar dropdown entry — exact href with closing quote, so
            // the form actions (/account/security/…) cannot match it.
            ->assertSee('href="'.self::PAGE.'"', false);
    }

    public function test_profile_form_updates_name_and_reflects_on_render(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $uid = $user->getAuthIdentifier();

        $response = $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/profile', [
                'name' => 'Renamed Operator',
            ]);

        $response->assertRedirect(self::PAGE);
        $response->assertSessionHas('flash', 'Profile updated.');

        $this->assertDatabaseHas('users', [
            'id' => $uid,
            'name' => 'Renamed Operator',
        ], 'central');
    }

    public function test_profile_form_rejects_disallowed_keys(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $uid = $user->getAuthIdentifier();

        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/profile', [
                'name' => 'Nope',
                'email' => 'new-email@example.test',
            ])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash_error');
        $this->assertStringContainsString('Unknown fields: email', (string) session('flash_error'));
        $this->assertDatabaseMissing('users', ['id' => $uid, 'name' => 'Nope'], 'central');
    }

    public function test_mfa_enrollment_confirmation_and_revocation_via_web_forms(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $uid = $user->getAuthIdentifier();

        // Step-up first: the console asks for an emailed code, and the
        // challenge reference rides the session for the second post.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/enroll/step-up')
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash', 'We sent a verification code to your email address. Enter it below to receive your authenticator setup key.');

        $challengeId = (string) session('mfa_enroll_stepup_challenge');
        $this->assertNotSame('', $challengeId, 'the pending challenge must reach the verify form');

        $token = $this->msg91TokenFor((string) $user->email);

        // Verify the step-up: the setup key now rides the flash — the page
        // never sees the enrollment envelope otherwise.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/enroll/verify', [
                'challenge_id' => $challengeId,
                'token' => $token,
            ])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash');
        $this->assertStringContainsString('Authenticator setup key', (string) session('flash'));
        $secret = $this->setupKeyFromFlash();
        $this->assertNotSame('', $secret, 'the flash must carry the manual-entry setup key');
        $this->assertDatabaseHas('users', ['id' => $uid, 'mfa_enabled' => false], 'central');

        // Wrong code → flash_error (envelope negotiation), still disabled.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/confirm', ['code' => '000000'])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash_error');
        $this->assertStringContainsString('Invalid or expired code', (string) session('flash_error'));
        $this->assertDatabaseHas('users', ['id' => $uid, 'mfa_enabled' => false], 'central');

        // Live code → enabled, recovery codes shown once on the flash.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/confirm', ['code' => $this->totpFor($secret)])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash');
        $flash = (string) session('flash');
        $this->assertStringStartsWith('Authenticator app enabled.', $flash);
        $this->assertStringContainsString('Recovery codes', $flash);
        $this->assertDatabaseHas('users', ['id' => $uid, 'mfa_enabled' => true], 'central');

        // Live code → revoked with its coded flash, disabled again.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/revoke', ['code' => $this->totpFor($secret)])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash', 'Authenticator app revoked.');
        $this->assertDatabaseHas('users', ['id' => $uid, 'mfa_enabled' => false], 'central');
    }

    public function test_recovery_codes_regenerate_via_web_form(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        // Reach the enabled state through the shared API flow (pinned by
        // MfaTest) — this test targets the web regenerate form.
        $challengeId = (string) $this->actingAs($user)
            ->postJson('/api/v1/identity/mfa/enroll/step-up')
            ->assertAccepted()
            ->json('data.challenge_id');

        $token = $this->msg91TokenFor((string) $user->email);

        $enrolled = $this->actingAs($user)
            ->postJson('/api/v1/identity/mfa/enroll/verify', [
                'challenge_id' => $challengeId,
                'token' => $token,
            ])
            ->assertCreated();
        parse_str((string) parse_url((string) $enrolled->json('data.otpauth_url'), PHP_URL_QUERY), $query);
        $secret = (string) ($query['secret'] ?? '');
        $this->assertNotSame('', $secret);
        $this->actingAs($user)
            ->postJson('/api/v1/identity/mfa/confirm', ['code' => $this->totpFor($secret)])
            ->assertOk();

        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/recovery-codes', ['code' => $this->totpFor($secret)])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash');
        $this->assertStringStartsWith('New recovery codes', (string) session('flash'));
    }

    public function test_stepup_email_requests_are_capped_with_a_coded_429_flash(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        // First issue succeeds
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/enroll/step-up')
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash');

        // Immediate subsequent request hits the delivery policy fresh gate -> 429
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/enroll/step-up')
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash_error');
        $this->assertStringContainsString('rate_limited', (string) session('flash_error'));
    }
}
