<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use OTPHP\TOTP;
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
    private const PAGE = 'http://localhost/account/security';

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

    public function test_security_page_renders_profile_mfa_and_session_sections(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->get('/account/security')
            ->assertOk()
            ->assertSee('id="profile-form"', false)
            ->assertSee('id="mfa-enroll-form"', false)
            ->assertSee('id="mfa-revoke-form"', false)
            ->assertSee('id="session-rows"', false)
            // Topbar dropdown entry — exact href with closing quote, so
            // the form actions (/account/security/…) cannot match it.
            ->assertSee('href="'.self::PAGE.'"', false);
    }

    public function test_profile_name_updates_via_web_form_and_unknown_fields_flash_errors(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $uid = $user->getAuthIdentifier();

        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/profile', ['name' => 'Renamed Operator'])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash', 'Profile updated.');
        $this->assertDatabaseHas('users', ['id' => $uid, 'name' => 'Renamed Operator'], 'central');

        // Strict unknown-field validation survives the widening: an extra
        // field negotiates to flash_error and writes nothing.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/profile', ['name' => 'Nope', 'email' => 'other@example.test'])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash_error');
        $this->assertStringContainsString('Unknown fields: email', (string) session('flash_error'));
        $this->assertDatabaseHas('users', ['id' => $uid, 'name' => 'Renamed Operator'], 'central');
    }

    public function test_mfa_enrollment_confirmation_and_revocation_via_web_forms(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $uid = $user->getAuthIdentifier();

        // Enroll: the setup key rides the flash — the page never sees
        // the enrollment envelope otherwise.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/mfa/enroll')
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
        $enrolled = $this->actingAs($user)
            ->postJson('/api/v1/identity/mfa/enroll')
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

    public function test_revoke_other_sessions_via_web_form(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        // A stolen session row for this user, older than the current one.
        DB::connection(config('database.default'))->table('sessions')->insert([
            'id' => 'stolen-session-row',
            'user_id' => $user->getAuthIdentifier(),
            'ip_address' => '203.0.113.9',
            'user_agent' => 'Evil Browser',
            'payload' => base64_encode(''),
            'last_activity' => time() - 300,
        ]);

        $this->withHeader('Referer', self::PAGE)
            ->actingAs($user)
            ->post('/account/security/sessions/revoke-others')
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash');
        $this->assertStringStartsWith('Other sessions revoked: ', (string) session('flash'));
        $this->assertDatabaseMissing('sessions', ['id' => 'stolen-session-row']);
    }
}
