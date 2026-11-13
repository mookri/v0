<?php

declare(strict_types=1);

namespace App\Modules\Support\Console;

use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportGrievance;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Scans open formal grievances for statutory acknowledgment and redressal SLA breaches (TS-11 / E-TS-03).
 */
final class ScanGrievanceSlaBreachesCommand extends Command
{
    protected $signature = 'support:scan-grievance-slas {--tenant= : Optional tenant ULID}';

    protected $description = 'Detect statutory grievance acknowledgment and redressal deadline breaches';

    public function handle(
        TenantContext $context,
        Outbox $outbox,
        AuditWriter $audit,
    ): int {
        $tenantIdOpt = $this->option('tenant');
        $tenants = Tenant::query()
            ->where('state', Tenant::STATE_ACTIVE)
            ->when($tenantIdOpt !== null, fn ($q) => $q->where('id', $tenantIdOpt))
            ->get();

        $breachedCount = 0;
        $now = CarbonImmutable::now('UTC');

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->clear();
            $context->set($tenantId, null, null, (new ConnectionResolver())->cachedFor($tenant), ['system' => 'grievance-sla-scan']);

            $grievances = SupportGrievance::query()
                ->where('tenant_id', $tenantId)
                ->where('formal_flag', true)
                ->where(function ($q): void {
                    $q->whereNull('breached_ack_at')->orWhereNull('breached_redress_at');
                })
                ->get();

            foreach ($grievances as $grievance) {
                $case = SupportCase::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $grievance->case_id)
                    ->first();

                if ($case === null || in_array((string) $case->status, [CaseStatus::CLOSED->value, CaseStatus::WITHDRAWN->value], true)) {
                    continue;
                }

                // 1. Statutory acknowledgment deadline breach
                if (
                    $grievance->breached_ack_at === null
                    && $case->acknowledged_at === null
                    && $grievance->statutory_ack_due_at !== null
                    && $now->greaterThanOrEqualTo($grievance->statutory_ack_due_at)
                ) {
                    $grievance->forceFill(['breached_ack_at' => $now->toDateTimeString()])->save();
                    $this->emitBreach($tenantId, $case, $grievance, 'statutory_ack', $outbox, $audit);
                    $breachedCount++;
                }

                // 2. Statutory redressal deadline breach
                if (
                    $grievance->breached_redress_at === null
                    && $case->resolved_at === null
                    && $grievance->statutory_redress_due_at !== null
                    && $now->greaterThanOrEqualTo($grievance->statutory_redress_due_at)
                ) {
                    $grievance->forceFill(['breached_redress_at' => $now->toDateTimeString()])->save();
                    $this->emitBreach($tenantId, $case, $grievance, 'statutory_redress', $outbox, $audit);
                    $breachedCount++;
                }
            }
        }

        $context->clear();
        $this->info("Scanned statutory grievance SLAs; recorded {$breachedCount} breach(es).");

        return self::SUCCESS;
    }

    private function emitBreach(
        string $tenantId,
        SupportCase $case,
        SupportGrievance $grievance,
        string $breachKind,
        Outbox $outbox,
        AuditWriter $audit,
    ): void {
        $audit->record(
            'support.grievance.breached',
            ['module' => 'support', 'resource_type' => 'support_grievance', 'resource_id' => (string) $grievance->getKey()],
            null,
            [
                'case_id' => (string) $case->getKey(),
                'case_reference' => $case->case_reference,
                'complaint_record_id' => $grievance->complaint_record_id,
                'breach_kind' => $breachKind,
            ]
        );

        $outbox->append(
            $tenantId,
            'support',
            (string) $grievance->getKey(),
            'support.grievance.breached',
            [
                'grievance_id' => (string) $grievance->getKey(),
                'case_id' => (string) $case->getKey(),
                'case_reference' => $case->case_reference,
                'complaint_record_id' => $grievance->complaint_record_id,
                'breach_kind' => $breachKind,
            ]
        );
    }
}
