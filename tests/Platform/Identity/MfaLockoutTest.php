<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\MfaService;
use OTPHP\TOTP;
use Tests\TenantIntegrationTestCase;

/**
 * MFA attempt lockout + TOTP replay (M4, gate G2): five failures lock
 * the factor for 15 minutes (429 + Retry-After at the API, correct code
 * included — fail closed), the window expires on its own, the counter
 * resets on success, and a burned TOTP step can never be replayed.
 */
final class MfaLockoutTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: User, 1: TOTP}
     */
    private function enrolledUser(): array
    {
        [$user] = $this->actingAsMembership($this->makeTenant());

        $enrolled = $this->actingAs($user)->postJson('/api/v1/identity/mfa/enroll')
            ->assertCreated()->assertJsonPath('data.object', 'mfa_enrollment');
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

        // Fifth failure trips the lock: 429 + Retry-After (contract §8).
        $locked = $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => '000000'])
            ->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');
        $this->assertNotNull($locked->headers->get('Retry-After'));
        $this->assertSame((string) MfaService::LOCKOUT_SECONDS, $locked->headers->get('Retry-After'));

        $fresh = $user->fresh();
        $this->assertSame(MfaService::LOCKOUT_ATTEMPTS, (int) $fresh->mfa_failed_attempts);
        $this->assertNotNull($fresh->mfa_locked_until);

        // Fail closed: the CORRECT code still waits out the window.
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->now()])
            ->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');
        auth()->logout();
    }

    public function test_lockout_expires_and_success_resets_the_counter(): void
    {
        [$user, $totp] = $this->enrolledUser();
        $actor = fn () => $this->actingAs($user);

        for ($i = 0; $i < MfaService::LOCKOUT_ATTEMPTS; $i++) {
            $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => '000000']);
        }
        auth()->logout();
        $this->assertNotNull($user->fresh()->mfa_locked_until);

        // Window elapses: the correct code passes and resets the state.
        User::query()->whereKey($user->getKey())
            ->update(['mfa_locked_until' => gmdate('Y-m-d H:i:s', time() - 1)]);
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->now()])
            ->assertOk()->assertJsonPath('data.verified', true);

        $fresh = $user->fresh();
        $this->assertSame(0, (int) $fresh->mfa_failed_attempts);
        $this->assertNull($fresh->mfa_locked_until);
        $this->assertNotNull($fresh->mfa_last_used_step);
        auth()->logout();
    }

    public function test_success_resets_a_partial_failure_run(): void
    {
        [$user, $totp] = $this->enrolledUser();
        $actor = fn () => $this->actingAs($user);

        for ($i = 0; $i < MfaService::LOCKOUT_ATTEMPTS - 2; $i++) {
            $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => '000000'])->assertUnauthorized();
        }
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $totp->now()])
            ->assertOk()->assertJsonPath('data.verified', true);

        $this->assertSame(0, (int) $user->fresh()->mfa_failed_attempts);
        auth()->logout();
    }

    public function test_a_burned_totp_step_cannot_be_replayed(): void
    {
        [$user, $totp] = $this->enrolledUser();
        $actor = fn () => $this->actingAs($user);
        $code = $totp->now();

        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $code])
            ->assertOk()->assertJsonPath('data.verified', true);

        // Same code, same step: rejected as an ordinary failure — and it
        // counts toward the lockout (a replay is an attack signal).
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $code])
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->assertSame(1, (int) $user->fresh()->mfa_failed_attempts);

        // The guard discriminates by burned step, not by wall time:
        // rolling the recorded step back makes the same code acceptable.
        User::query()->whereKey($user->getKey())
            ->update(['mfa_last_used_step' => (int) $user->fresh()->mfa_last_used_step - 1]);
        $user->refresh();
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $code])
            ->assertOk()->assertJsonPath('data.verified', true);
        auth()->logout();
    }

    public function test_recovery_codes_share_the_lockout_counter(): void
    {
        [$user, $totp] = $this->enrolledUser();
        $actor = fn () => $this->actingAs($user);
        auth()->logout();

        // Re-confirm to harvest a recovery sheet: revoke + re-enroll.
        $actor()->postJson('/api/v1/identity/mfa/revoke', ['code' => $totp->now()])->assertOk();
        $enrolled = $actor()->postJson('/api/v1/identity/mfa/enroll')->assertCreated();
        parse_str((string) parse_url($enrolled->json('data.otpauth_url'), PHP_URL_QUERY), $query);
        $fresh = TOTP::createFromSecret($query['secret']);
        $recovery = $actor()->postJson('/api/v1/identity/mfa/confirm', ['code' => $fresh->now()])
            ->assertOk()->json('data.recovery_codes');

        // 4 wrong TOTP + 1 wrong recovery code = 5 shared failures → lock.
        for ($i = 0; $i < MfaService::LOCKOUT_ATTEMPTS - 1; $i++) {
            $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => '000000'])->assertUnauthorized();
        }
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => 'NOT-A-CODE'])
            ->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');

        // The unused recovery code is real but must wait out the window.
        $actor()->postJson('/api/v1/identity/mfa/verify', ['code' => $recovery[0]])
            ->assertStatus(429);
        auth()->logout();
    }
}
