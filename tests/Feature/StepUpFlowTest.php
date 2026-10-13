<?php

namespace Tests\Feature;

use App\Models\User;
use App\Platform\Identity\Events\MfaEnrolled;
use App\Platform\Identity\Models\MfaStepUp;
use App\Platform\Identity\StepUpOtpService;
use Illuminate\Support\Facades\Event;
use OTPHP\TOTP;
use Tests\TenantIntegrationTestCase;

/**
 * Web step-up flow (SEC-004 §6, doc 06 §7.5): /oauth/stepup renders the
 * TOTP form (or the fail-closed enrollment notice) and answers the
 * console's freshness probe as JSON; its POST shares MfaController::verify,
 * so the proof row it writes is exactly what `stepup:2` reads. First
 * consumer: user suspend/activate share their API actions behind the same
 * tenant + capability + fresh-proof guards — web redirects back with a
 * coded flash, API envelopes stay untouched (the full suite is the parity
 * pin).
 */
final class StepUpFlowTest extends TenantIntegrationTestCase
{
    private function totpFromUrl(string $url): TOTP
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return TOTP::createFromSecret($query['secret']);
    }

    /**
     * Step-up → enroll → confirm through the API — the flow a real user runs
     * since I2 made enrollment itself require a step-up (scoped fake mirrors
     * MfaTest; lifecycle events are not this surface's contract).
     */
    private function enrollMfa(User $user): TOTP
    {
        Event::fake([MfaEnrolled::class]);

        $challengeId = $this->actingAs($user)
            ->postJson('/api/v1/identity/mfa/enroll/step-up')
            ->assertAccepted()
            ->json('data.challenge_id');

        $enrolled = $this->actingAs($user)
            ->postJson('/api/v1/identity/mfa/enroll/verify', [
                'challenge_id' => $challengeId,
                'code' => StepUpOtpService::getTestCode((string) $challengeId),
            ])
            ->assertCreated();

        $totp = $this->totpFromUrl($enrolled->json('data.otpauth_url'));

        $this->actingAs($user)
            ->postJson('/api/v1/identity/mfa/confirm', ['code' => $totp->now()])
            ->assertOk();

        return $totp;
    }

    public function test_stepup_page_renders_form_notice_and_json_probe(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        // No MFA → fail-closed notice, no code form at all.
        $this->actingAs($user)->get('/oauth/stepup')
            ->assertOk()
            ->assertSee('Multi-factor authentication is required', false)
            ->assertDontSee('name="code"', false);

        // The console gate's probe (fresh = skip the modal).
        $this->actingAs($user)->getJson('/oauth/stepup')
            ->assertOk()
            ->assertJsonPath('data.object', 'stepup_status')
            ->assertJsonPath('data.mfa_enabled', false)
            ->assertJsonPath('data.fresh', false);

        $this->enrollMfa($user);

        $this->actingAs($user)->get('/oauth/stepup?return_to=/platform/users')
            ->assertOk()
            ->assertSee('name="code"', false)
            ->assertSee('value="/platform/users"', false);

        $this->actingAs($user)->getJson('/oauth/stepup')
            ->assertJsonPath('data.mfa_enabled', true)
            ->assertJsonPath('data.fresh', false);

        // A fresh proof flips the probe — the gate's skip-path.
        $this->stepUp($user);
        $this->actingAs($user)->getJson('/oauth/stepup')
            ->assertJsonPath('data.fresh', true);
    }

    public function test_stepup_page_requires_a_session(): void
    {
        $this->get('/oauth/stepup')->assertRedirect('/login');
    }

    public function test_stepup_post_writes_the_proof_and_returns_safely(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $totp = $this->enrollMfa($user);

        // Success: proof row written (exactly what stepup:2 reads),
        // returned to the same-site target with a coded flash.
        $this->actingAs($user)
            ->post('/oauth/stepup', ['code' => $totp->now(), 'return_to' => '/platform/users'])
            ->assertRedirect('/platform/users')
            ->assertSessionHas('flash', 'Identity verified — sensitive actions are unlocked for 15 minutes.');

        $this->assertTrue(
            MfaStepUp::query()->where('user_id', (string) $user->getAuthIdentifier())->exists(),
        );

        // Open-redirect clamp: off-site targets collapse to `/`.
        $this->actingAs($user)
            ->post('/oauth/stepup', ['code' => $totp->now(), 'return_to' => 'https://evil.example/phish'])
            ->assertRedirect('/');
    }

    public function test_stepup_post_with_a_bad_code_fails_closed(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $this->enrollMfa($user);

        $response = $this->withHeader('Referer', 'http://localhost/platform/users')
            ->actingAs($user)
            ->post('/oauth/stepup', ['code' => 'not-a-code', 'return_to' => '/platform/users']);

        $response->assertRedirect('http://localhost/platform/users');
        $response->assertSessionHas('flash_error', 'unauthenticated: Invalid or expired code.');

        $this->assertFalse(
            MfaStepUp::query()->where('user_id', (string) $user->getAuthIdentifier())->exists(),
        );
    }

    public function test_web_suspend_and_activate_are_stepup_gated(): void
    {
        $tenant = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenant, ['platform:user:manage']);
        $target = User::factory()->create();
        $target->forceFill(['status' => 'active'])->save();

        $attempt = fn () => $this->withHeader('Referer', 'http://localhost/platform/users')
            ->actingAs($admin)
            ->post("/platform/users/{$target->getKey()}:suspend", [
                'tenant_id' => (string) $tenant->getKey(),
            ]);

        // Unenrolled → 403 page, no write (the shared action behind it
        // is untouched — fail-closed at the gate, not the controller).
        $attempt()->assertForbidden();
        $this->assertSame('active', $target->fresh()->status);

        // MFA enabled but no fresh proof → still held.
        $admin->forceFill(['mfa_enabled' => true])->save();
        $attempt()->assertForbidden();
        $this->assertSame('active', $target->fresh()->status);

        // A fresh proof (what /oauth/stepup writes) → the shared action
        // runs: redirect back + coded flash + credential kill.
        $this->stepUp($admin);
        $attempt()
            ->assertRedirect('http://localhost/platform/users')
            ->assertSessionHas('flash', 'User suspended.');
        $this->assertSame('suspended', $target->fresh()->status);

        // Activate: same gate, same action, proof still fresh.
        $this->withHeader('Referer', 'http://localhost/platform/users')
            ->actingAs($admin)
            ->post("/platform/users/{$target->getKey()}:activate", [
                'tenant_id' => (string) $tenant->getKey(),
            ])
            ->assertRedirect('http://localhost/platform/users')
            ->assertSessionHas('flash', 'User activated.');
        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_web_suspend_without_capability_fails_closed(): void
    {
        $tenant = $this->makeTenant();
        [$member] = $this->actingAsMembership($tenant);
        $target = User::factory()->create();
        $target->forceFill(['status' => 'active'])->save();

        // Arm the step-up gate (enabled + fresh) — only the capability
        // can still deny, and it does: 403 page, no write.
        $member->forceFill(['mfa_enabled' => true])->save();
        $this->stepUp($member);

        $this->actingAs($member)
            ->post("/platform/users/{$target->getKey()}:suspend", [
                'tenant_id' => (string) $tenant->getKey(),
            ])
            ->assertForbidden();

        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_users_page_renders_the_actions_column(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->get('/platform/users')
            ->assertOk()
            ->assertSee('id="user-rows"', false)
            ->assertSee('<th>Actions</th>', false);
    }
}
