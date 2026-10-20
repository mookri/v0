<?php

namespace Tests\Modules\Onboarding;

use App\Models\User;
use App\Modules\Onboarding\Models\OtpChallenge;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Identity\Models\Invite;
use App\Platform\Tenancy\TenantScope;
use Illuminate\Support\Facades\Artisan;
use Tests\TenantIntegrationTestCase;

/**
 * Retention sweeper: dry-run reports without writing, per-tenant scoping
 * holds across two tenants, live rows are never touched, expired OTP
 * challenges die, expired invites flip state (history retained), expired
 * sessions go.
 */
final class CleanupExpiredTest extends TenantIntegrationTestCase
{
    private function seedExpired(string $tenantId, string $suffix): void
    {
        OtpChallenge::withoutGlobalScope(TenantScope::class)->insert([
            'id' => "01J0000000000000000000{$suffix}",
            'tenant_id' => $tenantId,
            'identifier' => 'u1',
            'code_hash' => 'h',
            'expires_at' => '2020-01-01 00:00:00',
            'attempts' => 0,
            'max_attempts' => 3,
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        Invite::query()->create([
            'tenant_id' => $tenantId,
            'track' => 'pandit',
            'identifier' => "old-{$suffix}@example.com",
            'token_hash' => hash('sha256', "tok-{$suffix}"),
            'state' => Invite::STATE_ISSUED,
            'expires_at' => '2020-01-01 00:00:00',
        ]);
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $tenant = $this->makeTenant();
        $this->seedExpired((string) $tenant->getKey(), '101');

        $this->assertSame(0, Artisan::call('cleanup:expired', ['--dry-run' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('[dry-run]', $output);
        $this->assertSame(1, OtpChallenge::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', (string) $tenant->getKey())->count());
    }

    public function test_sweep_is_per_tenant_and_spares_live_rows(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        $this->seedExpired((string) $tenantA->getKey(), '201');
        $this->seedExpired((string) $tenantB->getKey(), '202');

        // Live challenge in A must survive.
        $live = new OtpChallenge();
        $live->forceFill([
            'tenant_id' => (string) $tenantA->getKey(),
            'identifier' => 'u-live',
            'code_hash' => 'h',
            'expires_at' => '2099-01-01 00:00:00',
            'attempts' => 0,
            'max_attempts' => 3,
        ]);
        $this->resolveContext($tenantA);
        try {
            $live->save();
        } finally {
            $this->clearContext();
        }

        $this->assertSame(0, Artisan::call('cleanup:expired', [
            '--force' => true,
            '--tenant' => (string) $tenantA->getKey(),
        ]));

        $this->assertSame(0, OtpChallenge::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', (string) $tenantA->getKey())
            ->where('expires_at', '<', '2021-01-01 00:00:00')->count());
        $this->assertSame(1, OtpChallenge::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', (string) $tenantA->getKey())->count());
        $this->assertSame(1, OtpChallenge::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', (string) $tenantB->getKey())->count());
        $this->assertSame(
            'EXPIRED',
            Invite::query()->where('identifier', 'old-201@example.com')->firstOrFail()->state
        );
        $this->assertSame(
            'ISSUED',
            Invite::query()->where('identifier', 'old-202@example.com')->firstOrFail()->state
        );
    }

    public function test_refuses_without_explicit_mode(): void
    {
        $this->assertSame(1, Artisan::call('cleanup:expired'));
    }

    public function test_step_up_proofs_sweep_after_24h(): void
    {
        $user = User::factory()->create();

        \App\Platform\Identity\Models\MfaStepUp::query()->create([
            'user_id' => $user->id,
            'verified_at' => now()->subHours(25)->toDateTimeString(),
        ]);
        \App\Platform\Identity\Models\MfaStepUp::query()->create([
            'user_id' => $user->id,
            'verified_at' => now()->toDateTimeString(),
        ]);

        $this->assertSame(0, Artisan::call('cleanup:expired', ['--force' => true]));

        $this->assertSame(1, \App\Platform\Identity\Models\MfaStepUp::query()->count());
        $this->assertSame(
            0,
            \App\Platform\Identity\Models\MfaStepUp::query()
                ->where('verified_at', '<', now()->subHours(24)->toDateTimeString())
                ->count()
        );
    }

    public function test_evidence_cap_rejects_without_partial_write(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $started = $this->actingAs($user)->postJson(
            '/api/v1/onboarding/applications',
            ['track' => 'pandit'],
            $headers
        )->assertCreated();
        $id = $started->json('data.id');

        // Just under the cap passes.
        $okPayload = ['value' => ['name' => str_repeat('n', 100), 'phone' => '+919876543210']];
        $this->actingAs($user)->putJson("/api/v1/onboarding/applications/{$id}/step/identity", $okPayload, $headers)
            ->assertOk();

        // 70KB of valid-shaped evidence trips the cap with no partial write.
        $big = $this->actingAs($user)->putJson(
            "/api/v1/onboarding/applications/{$id}/step/credentials",
            ['value' => ['certifications' => [str_repeat('c', 70000)]]],
            $headers
        );
        $big->assertStatus(413)->assertJsonPath('error.code', 'payload_too_large');

        $app = PartnerApplication::withoutGlobalScope(TenantScope::class)->findOrFail($id);
        $this->assertSame(100, strlen($app->evidence['identity']['name']));
        $this->assertSame('+919876543210', $app->evidence['identity']['phone']);
    }
}
