<?php

namespace App\Modules\Pandit\Console;

use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditAssignmentRule;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Sweeps pending pandit assignments that exceeded response SLA,
 * marks them DECLINED with SLA_TIMEOUT reason, and emits outbox events for reassignment.
 */
final class ProcessAssignmentSlaCommand extends Command
{
    protected $signature = 'pandit:process-assignment-sla {--tenant= : Scope to specific tenant}';

    protected $description = 'Process expired pending pandit assignments, auto-decline and emit SLA timeout events';

    public function handle(
        TenantContext $context,
        AuditWriter $audit,
        Outbox $outbox
    ): int {
        $tenantIdOption = $this->option('tenant');
        $tenants = Tenant::query()
            ->where('state', Tenant::STATE_ACTIVE)
            ->when($tenantIdOption !== null, fn($q) => $q->where('id', $tenantIdOption))
            ->get();

        $totalDeclined = 0;

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->clear();
            $context->set($tenantId, null, null, (new ConnectionResolver())->cachedFor($tenant), ['system' => 'sla-daemon']);

            $pendingAssignments = PanditAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('status', PanditAssignment::STATUS_PENDING)
                ->get();

            foreach ($pendingAssignments as $assignment) {
                $rule = PanditAssignmentRule::query()
                    ->where('tenant_id', $tenantId)
                    ->where('pandit_id', $assignment->pandit_id)
                    ->first();

                $slaHours = $rule !== null ? (int) $rule->response_sla_hours : 24;
                $deadline = CarbonImmutable::parse($assignment->assigned_at)->addHours($slaHours);

                if (now()->greaterThanOrEqualTo($deadline)) {
                    $assignment->forceFill([
                        'status' => PanditAssignment::STATUS_DECLINED,
                        'declined_at' => now(),
                        'decline_reason' => 'SLA_TIMEOUT',
                    ])->save();

                    $audit->record(
                        'pandit.assignment.sla_timeout',
                        ['module' => 'pandit', 'resource_type' => 'pandit_assignment', 'resource_id' => (string) $assignment->getKey()],
                        null,
                        ['pandit_id' => $assignment->pandit_id, 'booking_id' => $assignment->booking_id, 'sla_hours' => $slaHours]
                    );

                    $outbox->append(
                        $tenantId,
                        'pandit_assignment',
                        (string) $assignment->getKey(),
                        'pandit.assignment.declined',
                        [
                            'assignment_id' => (string) $assignment->getKey(),
                            'tenant_id' => $tenantId,
                            'pandit_id' => $assignment->pandit_id,
                            'booking_id' => $assignment->booking_id,
                            'reason' => 'SLA_TIMEOUT',
                            'action_required' => 'REASSIGN',
                        ]
                    );

                    $totalDeclined++;
                }
            }
        }

        $this->info("Processed SLA deadlines. Auto-declined {$totalDeclined} assignments.");

        return 0;
    }
}
