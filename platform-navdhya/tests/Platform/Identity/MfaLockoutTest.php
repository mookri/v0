<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\MfaService;
use OTPHP\TOTP;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * MFA attempt lockout + TOTP replay (M4, gate G2): five failures lock
 * the factor for 15 minutes (429 + Retry-After at the API, correct code
 * included — fail closed), the window expires on its own, the counter
 * resets on success, and a burned TOTP step can never be replayed.
 */
final class MfaLockoutTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
    }

    /**
     * @return array{0: User, 1: TOTP}
     */
    private function enrolledUser(): array
    {
        [$user] = $this->actingAsMembership($this->makeTenant());

        // I2: MFA enroll now requires step-up (stepup:2)
        $stepUp = $this->actingAs($user)->postJson('/api/v1/identity/mfa/enroll/step-up')
            ->assertAccepted()->assertJsonPath('data.object', 'otp_challenge');
        $challengeId = $stepUp->json('data.challenge_id');

        $token = $this->msg91TokenFor((string) $user->email);

        $enrolled = $this->actingAs($user)->postJson('/api/v1/identity/mfa/enroll/verify', [
            'challenge_id' => $challengeId,
            'token' => $token,
        ])->assertCreated()->assertJsonPath('data.object', 'mfa_enrollment');

        parse_str((string) parse_url($enrolled->json('data.otpauth_url'), PHP_URL_QUERY), $query);
        $totp = TOTP::createFromSecret($query['secret']);

        $this->actingAs($user)->postJson('/api/v1/identity/mfa/confirm', ['code' => $totp->now()])
            ->assertOk();
        auth()->logout();

        return [$user->fresh(), $totp];
    }

    public function test_five_failures_lock_out_and_even_the_correct_code_waits(): void
    {
        [$user, $totp] = $this->enrolledUser();
        $actor = fn () => $this->actingAs($user);

        // Four failures: collapsed 401s, no lock yet (G2 threshold).
        for ($i = 0; $i < MfaService::LOCKOUT_ATTEMPTS - 1; $i++) {
            $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => '000000'])
                ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        }
        $this->assertSame(MfaService::LOCKOUT_ATTEMPTS - 1, (int) $user->fresh()->mfa_failed_attempts);
        $this->assertNull($user->fresh()->mfa_locked_until);

        // Fifth failure: lockout trips; response is 429 with Retry-After.
        $locked = $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => '000000'])
            ->assertStatus(429);
        $this->assertSame(
            (string) MfaService::LOCKOUT_SECONDS,
            $locked->headers->get('Retry-After')
        );

        $user->refresh();
        $this->assertSame(MfaService::LOCKOUT_ATTEMPTS, (int) $user->mfa_failed_attempts);
        $this->assertNotNull($user->mfa_locked_until);

        // While locked, even the CORRECT code gets 429 (fail-closed).
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->now()])
            ->assertStatus(429);

        // Wind clock past the lockout: verify now succeeds with the correct code.
        $this->travel(MfaService::LOCKOUT_SECONDS + 1)->seconds();

        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->at(now()->timestamp)])
            ->assertOk();

        // Counter reset on success.
        $user->refresh();
        $this->assertSame(0, (int) $user->mfa_failed_attempts);
        $this->assertNull($user->mfa_locked_until);
    }

    public function test_success_resets_prior_failure_counter(): void
    {
        [$user, $totp] = $this->enrolledUser();
        $actor = fn () => $this->actingAs($user);

        // Three failed attempts.
        for ($i = 0; $i < 3; $i++) {
            $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => '000000'])
                ->assertUnauthorized();
        }
        $this->assertSame(3, (int) $user->fresh()->mfa_failed_attempts);

        // Correct code resets the counter.
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->now()])
            ->assertOk();

        $this->assertSame(0, (int) $user->fresh()->mfa_failed_attempts);
        $this->assertNull($user->fresh()->mfa_locked_until);
    }

    public function test_totp_timestep_replay_is_rejected(): void
    {
        [$user, $totp] = $this->enrolledUser();
        $actor = fn () => $this->actingAs($user);

        $code = $totp->now();

        // First verification with this timestep code succeeds.
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $code])
            ->assertOk();

        // Replaying the SAME timestep code in the same 30s window is rejected (401).
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $code])
            ->assertUnauthorized();

        // Advance into next timestep: fresh code works.
        $this->travel(31)->seconds();
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->at(now()->timestamp)])
            ->assertOk();
    }

    public function test_recovery_codes_share_the_lockout_counter(): void
    {
        [$user, $totp] = $this->enrolledUser();
        $actor = fn () => $this->actingAs($user);
        auth()->logout();

        // Re-confirm to harvest a recovery sheet: revoke + re-enroll.
        $actor()->postJson('/api/v1/identity/mfa/revoke', ['code' => $totp->now()])->assertOk();

        // I2: re-enroll requires step-up
        $stepUp = $actor()->postJson('/api/v1/identity/mfa/enroll/step-up')
            ->assertAccepted()->assertJsonPath('data.object', 'otp_challenge');
        $challengeId = $stepUp->json('data.challenge_id');

        $token = $this->msg91TokenFor((string) $user->email);

        $enrolled = $actor()->postJson('/api/v1/identity/mfa/enroll/verify', [
            'challenge_id' => $challengeId,
            'token' => $token,
        ])->assertCreated()->assertJsonPath('data.object', 'mfa_enrollment');

        parse_str((string) parse_url($enrolled->json('data.otpauth_url'), PHP_URL_QUERY), $query);
        $totp = TOTP::createFromSecret($query['secret']);

        // Confirm the NEW enrollment (this enables MFA and returns recovery codes)
        $recovery = $actor()->postJson('/api/v1/identity/mfa/confirm', ['code' => $totp->now()])
            ->assertOk()->json('data.recovery_codes');
        auth()->logout();

        // Use INVALID codes to trigger failures (recovery codes would succeed)
        for ($i = 0; $i < MfaService::LOCKOUT_ATTEMPTS; $i++) {
            $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => '000000']);
        }
        auth()->logout();

        // Lockout should apply to failed attempts (recovery or TOTP)
        $locked = $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->now()])
            ->assertStatus(429);
        $this->assertNotNull($locked->headers->get('Retry-After'));
        auth()->logout();
    }
}
