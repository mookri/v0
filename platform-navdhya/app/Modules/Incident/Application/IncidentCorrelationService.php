<?php

declare(strict_types=1);

namespace App\Modules\Incident\Application;

use App\Modules\Incident\Models\IncidentCorrelation;
use App\Modules\Incident\Models\IncidentTimeline;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Scored Incident Correlation Service (ADR-0064 / E-TS-05).
 *
 * Strictly proposes correlations with score & evidence; human confirmation is
 * required before linking, and any confirmed correlation is cleanly revocable.
 */
final class IncidentCorrelationService
{
    public function __construct(
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    public function propose(
        string $incidentId,
        string $correlationKey,
        float $score,
        array $evidence,
        ?string $candidateBookingId = null,
        ?string $candidateIncidentId = null,
    ): IncidentCorrelation {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        /** @var IncidentCorrelation $corr */
        $corr = IncidentCorrelation::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'candidate_booking_id' => $candidateBookingId,
            'candidate_incident_id' => $candidateIncidentId,
            'correlation_key' => $correlationKey,
            'score' => round(max(0.0, min(1.0, $score)), 2),
            'evidence_json' => $evidence,
            'status' => IncidentCorrelation::STATUS_PROPOSED,
        ]);

        return $corr;
    }

    public function confirm(string $correlationId, string $actorId): IncidentCorrelation
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        $corr = IncidentCorrelation::query()->where('tenant_id', $tenantId)->where('id', $correlationId)->firstOrFail();
        $corr->forceFill([
            'status' => IncidentCorrelation::STATUS_CONFIRMED,
            'confirmed_by_id' => $actorId,
            'decided_at' => $now->toDateTimeString(),
        ])->save();

        IncidentTimeline::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $corr->incident_id,
            'entry_type' => 'CORRELATION_CONFIRMED',
            'actor_id' => $actorId,
            'summary' => "Confirmed correlation {$corr->correlation_key} (score {$corr->score})",
            'occurred_at' => $now->toDateTimeString(),
            'created_at' => $now->toDateTimeString(),
        ]);

        $this->audit->record(
            'incident.correlation.confirmed',
            ['module' => 'incident', 'resource_type' => 'incident_correlation', 'resource_id' => $correlationId],
            null,
            ['incident_id' => $corr->incident_id, 'actor_id' => $actorId]
        );

        return $corr;
    }

    public function revoke(string $correlationId, string $actorId, string $reason): IncidentCorrelation
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Revocation reason is required.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        $corr = IncidentCorrelation::query()->where('tenant_id', $tenantId)->where('id', $correlationId)->firstOrFail();
        $corr->forceFill([
            'status' => IncidentCorrelation::STATUS_REVOKED,
            'revoked_by_id' => $actorId,
            'decided_at' => $now->toDateTimeString(),
        ])->save();

        IncidentTimeline::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $corr->incident_id,
            'entry_type' => 'CORRELATION_REVOKED',
            'actor_id' => $actorId,
            'summary' => "Revoked correlation {$corr->correlation_key}: {$reason}",
            'occurred_at' => $now->toDateTimeString(),
            'created_at' => $now->toDateTimeString(),
        ]);

        $this->audit->record(
            'incident.correlation.revoked',
            ['module' => 'incident', 'resource_type' => 'incident_correlation', 'resource_id' => $correlationId],
            null,
            ['incident_id' => $corr->incident_id, 'actor_id' => $actorId, 'reason' => $reason]
        );

        return $corr;
    }
}

