<?php

namespace Tests\Platform\Authz;

use App\Models\User;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\Models\Policy;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * platform:authz-explain (T-0K01-02.4): the CLI evaluates the SEC-022
 * chain for one membership outside a request context; --json emits
 * exactly the payload the explain endpoint returns under `data`, and
 * unknown memberships fail closed.
 */
final class AuthzExplainCommandTest extends TenantIntegrationTestCase
{
    public function test_json_output_matches_engine_decision_payload(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);

        $exitCode = Artisan::call('platform:authz-explain', [
            'membership' => (string) $membership->getKey(),
            'capability' => 'sales:order:approve',
            '--json' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $payload = json_decode(trim(Artisan::output()), true);

        $this->assertIsArray($payload, 'The --json output is a single machine-readable payload.');

        // The payload equals a direct engine evaluation for the same
        // inputs (T-0K01-02.4: CLI output matches the API explain shape).
        $this->resolveContext($tenant, $membership);
        $expected = app(PolicyEvaluationEngine::class)->evaluate(new AuthorizationRequest(
            principal: User::findOrFail($membership->user_id),
            capability: 'sales:order:approve',
            membershipId: (string) $membership->getKey(),
        ), bypassCache: true);
        $this->clearContext();

        $this->assertSame($expected->toArray(), $payload);
    }

    public function test_text_output_shows_decision_and_stage_trace(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);

        $this->activePolicy($tenant, 'deny');

        $this->artisan('platform:authz-explain', [
            'membership' => (string) $membership->getKey(),
            'capability' => 'sales:order:approve',
        ])
            ->expectsOutputToContain('deciding_stage: explicit_deny')
            ->expectsOutputToContain('[explicit_deny] deny')
            ->assertExitCode(0);
    }

    public function test_attribute_options_drive_condition_evaluation(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);

        $this->activePolicy($tenant, 'deny', [
            'conditions' => [['field' => 'amount', 'operator' => 'gt', 'value' => 10000]],
        ]);

        $this->artisan('platform:authz-explain', [
            'membership' => (string) $membership->getKey(),
            'capability' => 'sales:order:approve',
            '--amount' => '15000',
            '--json' => true,
        ])
            ->expectsOutputToContain('"deciding_stage": "explicit_deny"')
            ->assertExitCode(0);

        $this->artisan('platform:authz-explain', [
            'membership' => (string) $membership->getKey(),
            'capability' => 'sales:order:approve',
            '--amount' => '500',
            '--json' => true,
        ])
            ->expectsOutputToContain('"deciding_stage": "allow_grant"')
            ->assertExitCode(0);
    }

    public function test_unknown_membership_fails_closed(): void
    {
        $this->artisan('platform:authz-explain', [
            'membership' => 'missing-membership',
            'capability' => 'sales:order:approve',
        ])
            ->expectsOutputToContain("Membership 'missing-membership' not found (fail-closed).")
            ->assertExitCode(1);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activePolicy(Tenant $tenant, string $effect, array $overrides = []): Policy
    {
        $this->resolveContext($tenant);

        $policy = Policy::create($overrides + [
            'name' => 'policy-'.$effect.'-'.Str::lower(Str::random(8)),
            'effect' => $effect,
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            'status' => 'active',
        ]);

        $this->clearContext();

        return $policy;
    }
}
