<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Events\MfaEnrolled;
use App\Platform\Identity\Events\MfaRevoked;
use Illuminate\Support\Facades\Event;
use OTPHP\TOTP;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * TOTP second factor (SEC-004 §1.4): enroll → confirm (recovery codes
 * once) → verify (TOTP + single-use recovery) → revoke (TOTP-only),
 * with collapse of all code failures to one 401.
 */
final class MfaTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
    }

    private function totpFromUrl(string $url): TOTP
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return TOTP::createFromSecret($query['secret']);
    }

    public function test_enroll_confirm_verify_revoke_cycle(): void
    {
        Event::fake([MfaEnrolled::class, MfaRevoked::class]);

        [$user] = $this->actingAsMembership($this->makeTenant());
        $actor = fn () => $this->actingAs($user);

        // I2: MFA enroll now requires step-up (stepup:2)
        // 1. Create step-up challenge
        $stepUp = $actor()->postJson('/api/v1/identity/mfa/enroll/step-up')
            ->assertAccepted()->assertJsonPath('data.object', 'otp_challenge');
        $challengeId = $stepUp->json('data.challenge_id');

        $token = $this->msg91TokenFor((string) $user->email);

        // 2. Verify step-up OTP with token
        $enrolled = $actor()->postJson('/api/v1/identity/mfa/enroll/verify', [
            'challenge_id' => $challengeId,
            'token' => $token,
        ])->assertCreated()->assertJsonPath('data.object', 'mfa_enrollment');
        $totp = $this->totpFromUrl($enrolled->json('data.otpauth_url'));

        // Not enabled until confirmed.
        $actor()->getJson('/api/v1/identity/mfa')->assertOk()->assertJsonPath('data.enabled', false);
        $actor()->postJson('/api/v1/identity/mfa/confirm', ['code' => '000000'])
            ->assertUnauthorized();

        $confirmed = $actor()->postJson('/api/v1/identity/mfa/confirm', ['code' => $totp->now()])
            ->assertOk();
        $recovery = $confirmed->json('data.recovery_codes');
        $this->assertCount(10, $recovery);

        $actor()->getJson('/api/v1/identity/mfa')->assertOk()->assertJsonPath('data.enabled', true);
        Event::assertDispatched(MfaEnrolled::class);

        // Verify: TOTP works
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->now()])
            ->assertOk();

        // Single-use recovery works, but dies on reuse
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $recovery[0]])
            ->assertOk();
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $recovery[0]])
            ->assertUnauthorized();

        // Revoke requires live TOTP (not recovery)
        $actor()->postJson('/api/v1/identity/mfa/revoke', ['code' => $recovery[1]])
            ->assertUnauthorized();
        $actor()->postJson('/api/v1/identity/mfa/revoke', ['code' => $totp->now()])
            ->assertOk();

        $actor()->getJson('/api/v1/identity/mfa')->assertOk()->assertJsonPath('data.enabled', false);
        Event::assertDispatched(MfaRevoked::class);

        // Verification denied once revoked
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->now()])
            ->assertUnauthorized();
    }

    public function test_recovery_sheet_regeneration_voids_old(): void
    {
        [$user] = $this->actingAsMembership($this->makeTenant());
        $actor = fn () => $this->actingAs($user);

        // I2: MFA enroll now requires step-up (stepup:2)
        $stepUp = $actor()->postJson('/api/v1/identity/mfa/enroll/step-up')
            ->assertAccepted()->assertJsonPath('data.object', 'otp_challenge');
        $challengeId = $stepUp->json('data.challenge_id');

        $token = $this->msg91TokenFor((string) $user->email);

        $enrolled = $actor()->postJson('/api/v1/identity/mfa/enroll/verify', [
            'challenge_id' => $challengeId,
            'token' => $token,
        ])->assertCreated()->assertJsonPath('data.object', 'mfa_enrollment');
        $totp = $this->totpFromUrl($enrolled->json('data.otpauth_url'));

        $old = $actor()->postJson('/api/v1/identity/mfa/confirm', ['code' => $totp->now()])
            ->assertOk()->json('data.recovery_codes');

        // Recovery codes cannot mint a new sheet (TOTP only).
        $actor()->postJson('/api/v1/identity/mfa/recovery-codes', ['code' => $old[0]])
            ->assertUnauthorized();

        $fresh = $actor()->postJson('/api/v1/identity/mfa/recovery-codes', ['code' => $totp->now()])
            ->assertOk()->json('data.recovery_codes');

        $this->assertNotSame($old, $fresh);

        // First of the old codes is dead.
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $old[0]])
            ->assertUnauthorized();
        // First of the fresh codes works once.
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $fresh[0]])
            ->assertOk();
    }
}
