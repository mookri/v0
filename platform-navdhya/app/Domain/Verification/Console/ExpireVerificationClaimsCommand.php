<?php

namespace App\Domain\Verification\Console;

use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Projector\VerificationProjector;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * Transitions overdue active claims to EXPIRED, re-projects partner verification_status,
 * and writes tamper-evident onboarding.claim.expired audit entries (Closes T-18 / T-TR07-04.2).
 */
final class ExpireVerificationClaimsCommand extends Command
{
    protected $signature = 'verification:expire {--tenant= : Optional single tenant ID}';

    protected $description = 'Expire overdue verification claims and recompute subject verification status projections.';

    public function handle(
        VerificationProjector $projector,
        AuditWriter $audit,
        TenantContext $context,
    ): int {
        $now = now()->utc();
        $query = VerificationClaim::withoutGlobalScopes()
            ->where('status', ClaimStatus::GRANTED->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now);

        if (is_string($this->option('tenant')) && $this->option('tenant') !== '') {
            $query->where('tenant_id', $this->option('tenant'));
        }

        $expired = $query->get();
        $count = 0;

        foreach ($expired as $claim) {
            $claim->forceFill(['status' => ClaimStatus::EXPIRED->value])->save();
            $count++;

            $tenant = Tenant::query()->find($claim->tenant_id);
            if ($tenant === null) {
                continue;
            }

            $wasResolved = $context->isResolved();
            if (! $wasResolved) {
                $context->set(
                    (string) $tenant->getKey(),
                    null,
                    null,
                    $tenant->connection_name ?? 'tenant_shared',
                    ['type' => 'system', 'id' => 'verification:expire'],
                );
            }

            try {
                $audit->record(
                    'onboarding.claim.expired',
                    ['module' => 'onboarding', 'resource_type' => 'verification_claim', 'resource_id' => (string) $claim->getKey()],
                    ['status' => ClaimStatus::GRANTED->value],
                    ['status' => ClaimStatus::EXPIRED->value, 'claim_kind' => $claim->claim_kind, 'subject_id' => $claim->subject_id],
                );
            } finally {
                if (! $wasResolved) {
                    $context->clear();
                }
            }
        }

        $this->info("Expired {$count} overdue verification claims.");

        return self::SUCCESS;
    }
}
