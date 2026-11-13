<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Domain\Trust\References\TrustReferenceGenerator;
use App\Modules\Support\Domain\Grievance\StatutoryClockService;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportGrievance;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

/**
 * Statutory Grievance Lifecycle Service (E-TS-03 / TS-11).
 *
 * Guarantees:
 *  - Verbatim complaint copy archived on `local` storage immediately on registration
 *  - Statutory acknowledgment & redress deadlines derived from `trust_sla_policies`
 *  - Officer decision required prior to closing formal grievances
 *  - Public Grievance Officer disclosure sourced from configuration (zero hardcoded PII)
 */
final class GrievanceService
{
    public function __construct(
        private readonly StatutoryClockService $clocks,
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    public function registerFormalGrievance(
        string $caseId,
        string $verbatimComplaint,
        ?CarbonImmutable $intakeAt = null,
    ): SupportGrievance {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = $intakeAt ?? CarbonImmutable::now('UTC');

        $case = SupportCase::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $caseId)
            ->firstOrFail();

        $existing = SupportGrievance::query()
            ->where('tenant_id', $tenantId)
            ->where('case_id', $caseId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $complaintRecordId = TrustReferenceGenerator::mint(
            TrustReferenceGenerator::KIND_GRIEVANCE,
            $tenantId,
            (new SupportGrievance())->getConnectionName(),
            'support_grievances',
            'complaint_record_id',
        );

        $ack = $this->clocks->computeDueAt($tenantId, 'acknowledgment', $now);
        $redress = $this->clocks->computeDueAt($tenantId, 'redress', $now);

        $storagePath = sprintf('support/grievances/%s/%s/complaint-copy.json', $tenantId, $caseId);
        $copyPayload = [
            'complaint_record_id' => $complaintRecordId,
            'case_id' => $caseId,
            'case_reference' => $case->case_reference,
            'reporter_user_id' => $case->reporter_user_id,
            'subject' => $case->subject,
            'verbatim_complaint' => $verbatimComplaint,
            'intake_at' => $now->toJSON(),
            'statutory_ack_due_at' => $ack['due_at']->toJSON(),
            'statutory_redress_due_at' => $redress['due_at']->toJSON(),
            'policy_version' => $redress['policy_version'],
        ];

        Storage::disk('local')->put($storagePath, json_encode($copyPayload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        /** @var SupportGrievance $grievance */
        $grievance = SupportGrievance::query()->create([
            'tenant_id' => $tenantId,
            'case_id' => $caseId,
            'formal_flag' => true,
            'complaint_record_id' => $complaintRecordId,
            'verbatim_complaint' => $verbatimComplaint,
            'complaint_copy_storage_ref' => $storagePath,
            'copy_issued_at' => $now->toDateTimeString(),
            'statutory_ack_due_at' => $ack['due_at']->toDateTimeString(),
            'statutory_redress_due_at' => $redress['due_at']->toDateTimeString(),
            'policy_version' => $redress['policy_version'],
        ]);

        $this->audit->record(
            'support.grievance.copy_issued',
            ['module' => 'support', 'resource_type' => 'support_grievance', 'resource_id' => (string) $grievance->getKey()],
            null,
            [
                'case_id' => $caseId,
                'complaint_record_id' => $complaintRecordId,
                'storage_ref' => $storagePath,
                'policy_version' => $redress['policy_version'],
            ]
        );

        $this->outbox->append(
            $tenantId,
            'support',
            (string) $grievance->getKey(),
            'support.grievance.copy_issued',
            [
                'grievance_id' => (string) $grievance->getKey(),
                'case_id' => $caseId,
                'case_reference' => $case->case_reference,
                'complaint_record_id' => $complaintRecordId,
                'statutory_ack_due_at' => $ack['due_at']->toJSON(),
                'statutory_redress_due_at' => $redress['due_at']->toJSON(),
            ]
        );

        return $grievance;
    }

    public function recordOfficerDecision(
        string $caseId,
        string $officerId,
        string $decision,
    ): SupportGrievance {
        if (trim($decision) === '') {
            throw new \InvalidArgumentException('Grievance Officer decision cannot be empty.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $grievance = SupportGrievance::query()
            ->where('tenant_id', $tenantId)
            ->where('case_id', $caseId)
            ->firstOrFail();

        $grievance->forceFill([
            'officer_id' => $officerId,
            'officer_decision' => trim($decision),
        ])->save();

        $this->audit->record(
            'support.grievance.decision_recorded',
            ['module' => 'support', 'resource_type' => 'support_grievance', 'resource_id' => (string) $grievance->getKey()],
            null,
            ['case_id' => $caseId, 'officer_id' => $officerId]
        );

        return $grievance;
    }

    /**
     * @return array<string, mixed>
     */
    public function complaintCopyPayload(string $caseId): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $grievance = SupportGrievance::query()
            ->where('tenant_id', $tenantId)
            ->where('case_id', $caseId)
            ->firstOrFail();

        $case = SupportCase::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $caseId)
            ->firstOrFail();

        $archived = null;
        if ($grievance->complaint_copy_storage_ref !== null && Storage::disk('local')->exists($grievance->complaint_copy_storage_ref)) {
            $raw = Storage::disk('local')->get($grievance->complaint_copy_storage_ref);
            if ($raw !== null) {
                $archived = json_decode($raw, true);
            }
        }

        return [
            'complaint_record_id' => $grievance->complaint_record_id,
            'case_id' => $caseId,
            'case_reference' => $case->case_reference,
            'verbatim_complaint' => $archived['verbatim_complaint'] ?? $grievance->verbatim_complaint,
            'copy_issued_at' => $grievance->copy_issued_at?->toJSON(),
            'statutory_ack_due_at' => $grievance->statutory_ack_due_at?->toJSON(),
            'statutory_redress_due_at' => $grievance->statutory_redress_due_at?->toJSON(),
            'officer_decision' => $grievance->officer_decision,
            'policy_version' => $grievance->policy_version,
            'grievance_officer' => $this->publicGrievanceOfficerInfo(),
        ];
    }

    /**
     * Public Grievance Officer disclosure (TS-11) — sourced strictly from config.
     *
     * @return array<string, mixed>
     */
    public function publicGrievanceOfficerInfo(): array
    {
        return [
            'name' => (string) config('trust.grievance_officer.name', 'Designated Grievance Officer, Navdhya'),
            'designation' => (string) config('trust.grievance_officer.designation', 'Grievance Redressal Officer'),
            'email' => (string) config('trust.grievance_officer.email', 'grievance-officer@navdhya.example'),
            'office_address' => (string) config('trust.grievance_officer.address', 'Navdhya Grievance Desk (Configure via NAVDHYA_GRIEVANCE_OFFICER_ADDRESS)'),
            'intake_channels' => ['web_support_portal', 'email', 'written_post'],
            'acknowledgment_policy' => 'Statutory acknowledgment deadline governed by approved SLA policy schedule.',
            'redressal_policy' => 'Statutory redressal deadline follows approved calendar-month handling rules.',
        ];
    }
}

