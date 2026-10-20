<?php

namespace App\Platform\Authz\Console;

use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Authz\PrincipalResolver;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * `php artisan platform:authz-explain` (T-0K01-02.4): debugging command
 * evaluating the SEC-022 chain for one membership + capability outside a
 * request context. The tenant context is resolved from the membership
 * itself and cleared afterwards (fail-closed on unknown memberships).
 * `--json` emits exactly the payload the GET /api/v1/authz/explain
 * endpoint returns under `data`.
 */
final class AuthzExplainCommand extends Command
{
    protected $signature = 'platform:authz-explain
        {membership : The tenant membership id to evaluate}
        {capability : The capability code to evaluate}
        {--amount= : Amount attribute for condition evaluation}
        {--currency= : Currency attribute (3-letter uppercase code)}
        {--org= : Org attribute}
        {--time= : Time attribute (parseable timestamp string)}
        {--status= : Status attribute}
        {--json : Emit only the machine-readable decision payload}';

    protected $description = 'Explain the SEC-022 precedence-chain decision for a membership and capability';

    public function handle(PolicyEvaluationEngine $engine, PrincipalResolver $principals, TenantContext $context): int
    {
        $membershipId = (string) $this->argument('membership');
        $membership = TenantMembership::query()->whereKey($membershipId)->first();

        if ($membership === null) {
            $this->error(sprintf("Membership '%s' not found (fail-closed).", $membershipId));

            return self::FAILURE;
        }

        $context->set((string) $membership->tenant_id, (string) $membership->getKey());

        try {
            $decision = $engine->evaluate(new AuthorizationRequest(
                principal: $principals->resolveById($membership->user_id),
                capability: (string) $this->argument('capability'),
                membershipId: (string) $membership->getKey(),
                attributes: $this->attributes(),
            ), bypassCache: true);
        } finally {
            $context->clear();
        }

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($decision->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->line(sprintf('decision:       %s', $decision->decision));
        $this->line(sprintf('deciding_stage: %s', $decision->decidingStage->value));
        $this->line(sprintf('capability:     %s', $decision->capability));
        $this->line(sprintf('membership:     %s', $membershipId));
        $this->newLine();

        foreach ($decision->stages as $stage) {
            $reason = $stage['reason'] ?? null;

            $this->line(sprintf(
                '  [%s] %s%s',
                (string) $stage['stage'],
                (string) $stage['outcome'],
                $reason !== null ? ' — '.(string) $reason : '',
            ));
        }

        $this->newLine();
        $this->line('payload:');
        $this->line((string) json_encode($decision->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(): array
    {
        $attributes = [];

        foreach (['amount', 'currency', 'org', 'time', 'status'] as $field) {
            $value = $this->option($field);

            if (is_string($value) && $value !== '') {
                $attributes[$field] = $value;
            }
        }

        return $attributes;
    }
}
