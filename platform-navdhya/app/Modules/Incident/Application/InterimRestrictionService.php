<?php

declare(strict_types=1);

namespace App\Modules\Incident\Application;

use App\Modules\Incident\Models\IncidentInterimRestriction;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Time-Boxed Proportionate Interim Protective Restrictions (E-TS-07 / TS-07).
 *
 * Strictly requires Safety Lead approval, written proportionality note,
 * and a bounded expiry <= 30 days (no silent indefinite blacklists).
 */
final class InterimRestrictionService
{
    public const ALLOWED_KINDS = ['QUEUE_HOLD', 'ASSIGNMENT_PAUSE', 'TEMP_SUSPENSION'];

    public const MAX_DURATION_DAYS = 30;

    public function __construct(
        private readonly AuditWriter $audit,
    ) {
    }

    public function impose(
        string $subjectType,
        string $subjectId,
        string $restrictionKind,
        string $reason,
        string $proportionalityNote,
        string $requestedById,
        string $safetyLeadId,
        CarbonImmutable $expiresAt,
        ?string $incidentId = null,
        ?string $supportCaseId = null,
    ): IncidentInterimRestriction {
        $kind = strtoupper($restrictionKind);
        if (! in_array($kind, self::ALLOWED_KINDS, true)) {
            throw new \InvalidArgumentException("Unsupported interim restriction kind: {$kind}");
        }

        $now = CarbonImmutable::now('UTC');
        if ($expiresAt->lessThanOrEqualTo($now)) {
            throw new \InvalidArgumentException('Interim restriction expiry must be in the future.');
        }

        if ($now->diffInDays($expiresAt) > self::MAX_DURATION_DAYS) {
            throw new \InvalidArgumentException('expiry_exceeds_cap: Time-boxed interim protective restrictions cannot exceed 30 days without review.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();

        /** @var IncidentInterimRestriction $rec */
        $rec = IncidentInterimRestriction::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'support_case_id' => $supportCaseId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'restriction_kind' => $kind,
            'reason' => $reason,
            'proportionality_note' => $proportionalityNote,
            'requested_by_id' => $requestedById,
            'approved_by_safety_lead_id' => $safetyLeadId,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);

        $this->audit->record(
            'incident.interim_restriction.imposed',
            ['module' => 'incident', 'resource_type' => 'incident_interim_restriction', 'resource_id' => (string) $rec->getKey()],
            null,
            ['subject_type' => $subjectType, 'subject_id' => $subjectId, 'kind' => $kind, 'expires_at' => $expiresAt->toJSON()]
        );

        return $rec;
    }

    public function lift(string $restrictionId, string $safetyLeadId): IncidentInterimRestriction
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $rec = IncidentInterimRestriction::query()->where('tenant_id', $tenantId)->where('id', $restrictionId)->firstOrFail();

        $rec->forceFill([
            'lifted_at' => CarbonImmutable::now('UTC')->toDateTimeString(),
            'lifted_by_id' => $safetyLeadId,
        ])->save();

        return $rec;
    }
}

