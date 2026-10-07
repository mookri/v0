<?php

namespace Tests\Feature;

use App\Platform\Identity\Models\MfaStepUp;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * API keys console surface (plan slice 8, doc 06 §7 + §7.5): the page
 * plus the shared issue/rotate/revoke actions behind the step-up gate —
 * web form posts negotiate a redirect + coded flash, and the one-time
 * secret rides that flash (the envelope's secret has no other web path);
 * API envelopes stay untouched (ApiKeyTest + the full suite are the
 * parity pin).
 */
final class AdminApiKeysSurfaceTest extends TenantIntegrationTestCase
{
    public function test_issue_via_web_is_stepup_gated_and_shows_the_secret_once(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();

        $issue = fn () => $this->withHeader('Referer', 'http://localhost/platform/api-keys')
            ->actingAs($issuer)
            ->post('/platform/api-keys', [
                'tenant_id' => $tid,
                'name' => 'console-key',
            ]);

        // No fresh proof → the gate holds (403 page), no write.
        $issue()->assertForbidden();
        $this->assertDatabaseCount('api_keys', 0, 'central');

        $this->stepUp($issuer);
        $issue()
            ->assertRedirect('http://localhost/platform/api-keys')
            ->assertSessionHas('flash');
        $flash = (string) session('flash');
        $this->assertStringStartsWith('API key issued', $flash);
        $this->assertStringContainsString('sk_live_', $flash);
        $this->assertDatabaseCount('api_keys', 1, 'central');

        // Validation failures redirect back with $errors (framework
        // negotiation) — still inside the fresh proof, no write.
        $this->withHeader('Referer', 'http://localhost/platform/api-keys')
            ->actingAs($issuer)
            ->post('/platform/api-keys', ['tenant_id' => $tid, 'name' => ''])
            ->assertRedirect('http://localhost/platform/api-keys')
            ->assertSessionHasErrors('name');
        $this->assertDatabaseCount('api_keys', 1, 'central');
    }

    public function test_revoke_and_rotate_via_web_share_the_action_and_gate(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $this->stepUp($issuer);
        $authed = fn () => $this->actingAs($issuer);

        $keyId = $authed()
            ->postJson('/api/v1/platform/api-keys', ['tenant_id' => $tid, 'name' => 'pipeline'])
            ->assertCreated()
            ->json('data.id');

        // Age the proof out: both mutations hold (403 page), no writes.
        MfaStepUp::query()
            ->where('user_id', $issuer->getAuthIdentifier())
            ->update(['verified_at' => gmdate('Y-m-d H:i:s', time() - 16 * 60)]);

        $attempt = fn (string $suffix) => $this->withHeader('Referer', 'http://localhost/platform/api-keys')
            ->actingAs($issuer)
            ->post("/platform/api-keys/{$keyId}{$suffix}", ['tenant_id' => $tid]);

        $attempt(':rotate')->assertForbidden();
        $attempt(':revoke')->assertForbidden();
        $this->assertDatabaseCount('api_keys', 1, 'central');
        $this->assertDatabaseHas('api_keys', ['id' => $keyId, 'revoked_at' => null], 'central');

        // Fresh proof → rotate flashes the new one-time secret; the old
        // row gains its 24h overlap expiry (it was issued without TTL).
        $this->stepUp($issuer);
        $rotated = $attempt(':rotate')
            ->assertRedirect('http://localhost/platform/api-keys')
            ->assertSessionHas('flash');
        $this->assertStringStartsWith('API key rotated', (string) session('flash'));
        $this->assertStringContainsString('sk_live_', (string) session('flash'));
        $this->assertDatabaseCount('api_keys', 2, 'central');
        $old = DB::connection('central')
            ->table('api_keys')->where('id', $keyId)->first();
        $this->assertNotNull($old?->expires_at, 'rotated key must enter its overlap window');

        // Proof still fresh → revoke the old key with its coded flash.
        $attempt(':revoke')
            ->assertRedirect('http://localhost/platform/api-keys')
            ->assertSessionHas('flash', 'API key revoked.');
        $revoked = DB::connection('central')
            ->table('api_keys')->where('id', $keyId)->first();
        $this->assertNotNull($revoked?->revoked_at, 'old key must be revoked');
    }

    public function test_keys_page_renders_issue_form_and_actions(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->get('/platform/api-keys')
            ->assertOk()
            ->assertSee('id="api-key-create-form"', false)
            ->assertSee('id="api-key-rows"', false)
            ->assertSee('<th>Actions</th>', false);
    }
}
