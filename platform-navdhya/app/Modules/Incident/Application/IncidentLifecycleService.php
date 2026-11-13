<?php

declare(strict_types=1);

namespace App\Modules\Incident\Application;

use App\Domain\Trust\References\TrustReferenceGenerator;
use App\Modules\Incident\Contracts\ManagesIncidents;
use App\Modules\Incident\Domain\IncidentLifecycle;
use App\Modules\Incident\Models\Incident;
use App\Modules\Incident\Models\IncidentAction;
use App\Modules\Incident\Models\IncidentCommunication;
use App\Modules\Incident\Models\IncidentImpact;
use App\Modules\Incident\Models\IncidentPostmortem;
use App\Modules\Incident\Models\IncidentPreventionTask;
use App\Modules\Incident\Models\IncidentRole;
use App\Modules\Incident\Models\IncidentSeverityHistory;
use App\Modules\Incident\Models\IncidentTimeline;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Authoritative Ceremony & Platform Incident Lifecycle Engine (E-TS-05 / TS-12 / ADR-0063).
 *
 * Implements ManagesIncidents contract for Booking, Support, and Feedback quality attribution (§6.3).
 */
final class IncidentLifecycleService implements ManagesIncidents
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    public function openForBooking(
        string $bookingId,
        string $type,
        string $severity,
        string $title,
        string $attributedCause = 'UNKNOWN',
    ): string {
        $incident = $this->declareIncident([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'attributed_cause' => $attributedCause,
            'actor_id' => 'system',
            'impacts' => [
                [
                    'booking_id' => $bookingId,
                    'attributed_cause' => $attributedCause,
                    'devotee_outcome' => 'PENDING',
                ],
            ],
        ]);

        return (string) $incident->getKey();
    }

    public function impactsForBookings(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();

        return IncidentImpact::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('booking_id', $bookingIds)
            ->get(['booking_id', 'attributed_cause', 'devotee_outcome'])
            ->map(static fn (IncidentImpact $impact): array => [
                'booking_id' => $impact->booking_id,
                'attributed_cause' => $impact->attributed_cause,
                'devotee_outcome' => (string) ($impact->devotee_outcome ?? 'PENDING'),
            ])
            ->values()
            ->all();
    }

    public function incidentSummaryForBooking(string $bookingId): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $impacts = IncidentImpact::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->get();

        $results = [];
        foreach ($impacts as $impact) {
            $inc = Incident::query()->where('tenant_id', $tenantId)->where('id', $impact->incident_id)->first();
            if ($inc === null) {
                continue;
            }
            $taskIds = IncidentPreventionTask::query()
                ->where('tenant_id', $tenantId)
                ->where('incident_id', (string) $inc->getKey())
                ->pluck('id')
                ->map(static fn (mixed $v): string => (string) $v)
                ->all();

            $results[] = [
                'incident_id' => (string) $inc->getKey(),
                'incident_reference' => $inc->incident_reference,
                'type' => $inc->type,
                'severity' => $inc->severity,
                'status' => $inc->status,
                'attributed_cause' => $impact->attributed_cause,
                'prevention_task_ids' => $taskIds,
            ];
        }

        return $results;
    }

    /**
     * @param  array{
     *   type: string,
     *   severity: string,
     *   title: string,
     *   summary?: string|null,
     *   attributed_cause?: string,
     *   actor_id: string,
     *   commander_id?: string|null,
     *   safety_lead_id?: string|null,
     *   impacts?: list<array{booking_id: string, subject_type?: string|null, subject_id?: string|null, attributed_cause?: string, devotee_outcome?: string|null}>,
     * }  $input
     */
    public function declareIncident(array $input): Incident
    {
        $type = strtoupper((string) $input['type']);
        $severity = strtoupper((string) $input['severity']);
        $cause = strtoupper((string) ($input['attributed_cause'] ?? 'UNKNOWN'));

        if (! in_array($type, IncidentLifecycle::TYPES, true)) {
            throw new \InvalidArgumentException("Unsupported incident type: {$type}");
        }
        if (! array_key_exists($severity, IncidentLifecycle::SEVERITY_ORDER)) {
            throw new \InvalidArgumentException("Unsupported incident severity: {$severity}");
        }
        if (! in_array($cause, IncidentLifecycle::CAUSES, true)) {
            throw new \InvalidArgumentException("Unsupported attributed cause: {$cause}");
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');
        $ref = TrustReferenceGenerator::mint(
            TrustReferenceGenerator::KIND_INCIDENT,
            $tenantId,
            (new Incident())->getConnectionName(),
            'incidents',
            'incident_reference',
        );

        $safetyRestricted = IncidentLifecycle::isSafetyType($type, $severity);

        return (new Incident())->getConnection()->transaction(function () use (
            $tenantId,
            $ref,
            $type,
            $severity,
            $cause,
            $input,
            $safetyRestricted,
            $now
        ): Incident {
            $incident = new Incident();
            $incident->forceFill([
                'tenant_id' => $tenantId,
                'incident_reference' => $ref,
                'type' => $type,
                'severity' => $severity,
                'status' => 'DECLARED',
                'attributed_cause' => $cause,
                'title' => $input['title'],
                'summary' => $input['summary'] ?? null,
                'commander_id' => $input['commander_id'] ?? $input['actor_id'],
                'safety_lead_id' => $input['safety_lead_id'] ?? null,
                'safety_restricted' => $safetyRestricted,
                'detected_at' => $now->toDateTimeString(),
            ]);
            $incident->save();

            $incidentId = (string) $incident->getKey();

            IncidentTimeline::query()->create([
                'tenant_id' => $tenantId,
                'incident_id' => $incidentId,
                'entry_type' => 'DECLARED',
                'actor_id' => $input['actor_id'],
                'summary' => "Incident declared as {$severity} ({$type}) — cause: {$cause}",
                'occurred_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
            ]);

            foreach ($input['impacts'] ?? [] as $impact) {
                IncidentImpact::query()->create([
                    'tenant_id' => $tenantId,
                    'incident_id' => $incidentId,
                    'booking_id' => $impact['booking_id'],
                    'subject_type' => $impact['subject_type'] ?? null,
                    'subject_id' => $impact['subject_id'] ?? null,
                    'attributed_cause' => strtoupper((string) ($impact['attributed_cause'] ?? $cause)),
                    'devotee_outcome' => $impact['devotee_outcome'] ?? 'PENDING',
                ]);
            }

            $this->audit->record(
                'incident.record.created',
                ['module' => 'incident', 'resource_type' => 'incident', 'resource_id' => $incidentId],
                null,
                ['reference' => $ref, 'type' => $type, 'severity' => $severity, 'safety_restricted' => $safetyRestricted]
            );

            $this->outbox->append(
                $tenantId,
                'incident',
                $incidentId,
                'incident.record.created',
                ['incident_id' => $incidentId, 'reference' => $ref, 'type' => $type, 'severity' => $severity, 'safety_restricted' => $safetyRestricted]
            );

            return $incident;
        });
    }

    public function assignRole(string $incidentId, string $role, string $actorId, string $assignedById): IncidentRole
    {
        $role = strtoupper($role);
        if (! in_array($role, IncidentLifecycle::COMMAND_ROLES, true)) {
            throw new \InvalidArgumentException("Unsupported incident command role: {$role}");
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');
        $incident = Incident::query()->where('tenant_id', $tenantId)->where('id', $incidentId)->firstOrFail();

        $record = IncidentRole::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'role' => $role,
            'actor_id' => $actorId,
            'assigned_by_id' => $assignedById,
            'assigned_at' => $now->toDateTimeString(),
        ]);

        if ($role === 'COMMANDER') {
            $incident->forceFill(['commander_id' => $actorId])->save();
        }
        if ($role === 'SAFETY_LEAD') {
            $incident->forceFill(['safety_lead_id' => $actorId])->save();
        }

        IncidentTimeline::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'entry_type' => 'ROLE_ASSIGNED',
            'actor_id' => $assignedById,
            'summary' => "Assigned {$role} to actor {$actorId}",
            'occurred_at' => $now->toDateTimeString(),
            'created_at' => $now->toDateTimeString(),
        ]);

        return $record;
    }

    public function changeSeverity(string $incidentId, string $newSeverity, string $actorId, string $reason): Incident
    {
        $newSeverity = strtoupper($newSeverity);
        if (! array_key_exists($newSeverity, IncidentLifecycle::SEVERITY_ORDER)) {
            throw new \InvalidArgumentException("Invalid severity: {$newSeverity}");
        }
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Severity change reason cannot be empty.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');
        $incident = Incident::query()->where('tenant_id', $tenantId)->where('id', $incidentId)->firstOrFail();
        $prev = $incident->severity;

        if ($prev === $newSeverity) {
            return $incident;
        }

        $isDowngrade = IncidentLifecycle::isDowngrade($prev, $newSeverity);

        IncidentSeverityHistory::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'previous_severity' => $prev,
            'new_severity' => $newSeverity,
            'actor_id' => $actorId,
            'reason' => trim($reason),
            'is_downgrade' => $isDowngrade,
            'occurred_at' => $now->toDateTimeString(),
        ]);

        $incident->forceFill([
            'severity' => $newSeverity,
            'safety_restricted' => $incident->safety_restricted || IncidentLifecycle::isSafetyType($incident->type, $newSeverity),
        ])->save();

        IncidentTimeline::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'entry_type' => 'SEVERITY_CHANGED',
            'actor_id' => $actorId,
            'summary' => "Severity changed from {$prev} to {$newSeverity} (".($isDowngrade ? 'DOWNGRADE' : 'ESCALATION')."): {$reason}",
            'occurred_at' => $now->toDateTimeString(),
            'created_at' => $now->toDateTimeString(),
        ]);

        $this->outbox->append(
            $tenantId,
            'incident',
            $incidentId,
            'incident.severity.changed',
            ['incident_id' => $incidentId, 'from' => $prev, 'to' => $newSeverity, 'is_downgrade' => $isDowngrade]
        );

        return $incident;
    }

    public function setImpactOutcome(
        string $incidentId,
        string $bookingId,
        string $attributedCause,
        string $devoteeOutcome,
        ?string $notes = null,
    ): IncidentImpact {
        $cause = strtoupper($attributedCause);
        if (! in_array($cause, IncidentLifecycle::CAUSES, true)) {
            throw new \InvalidArgumentException("Unsupported attributed cause: {$cause}");
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();

        /** @var IncidentImpact $impact */
        $impact = IncidentImpact::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'incident_id' => $incidentId,
                'booking_id' => $bookingId,
            ],
            [
                'attributed_cause' => $cause,
                'devotee_outcome' => strtoupper($devoteeOutcome),
                'notes' => $notes,
            ]
        );

        Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $incidentId)
            ->update(['attributed_cause' => $cause]);

        return $impact;
    }

    public function recordPostmortem(
        string $incidentId,
        string $authorId,
        string $summary,
        string $rootCauseNotes,
        array $contributingFactors = [],
    ): IncidentPostmortem {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        /** @var IncidentPostmortem $pm */
        $pm = IncidentPostmortem::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'incident_id' => $incidentId],
            [
                'summary' => $summary,
                'root_cause_notes' => $rootCauseNotes,
                'contributing_factors' => $contributingFactors,
                'authored_by_id' => $authorId,
                'completed_at' => $now->toDateTimeString(),
            ]
        );

        Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $incidentId)
            ->update(['postmortem_completed_at' => $now->toDateTimeString()]);

        return $pm;
    }

    public function addPreventionTask(
        string $incidentId,
        string $title,
        string $taskType,
        string $ownerId,
        ?string $linkedCaseId = null,
    ): IncidentPreventionTask {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        /** @var IncidentPreventionTask $task */
        $task = IncidentPreventionTask::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'title' => $title,
            'task_type' => strtoupper($taskType),
            'owner_id' => $ownerId,
            'status' => 'OPEN',
            'linked_case_id' => $linkedCaseId,
        ]);

        $this->outbox->append(
            $tenantId,
            'incident',
            (string) $task->getKey(),
            'incident.prevention.created',
            ['task_id' => (string) $task->getKey(), 'incident_id' => $incidentId, 'linked_case_id' => $linkedCaseId]
        );

        return $task;
    }

    public function recordCommunication(
        string $incidentId,
        string $audience,
        string $channel,
        string $recipientRef,
        string $messageSummary,
        string $approvedById,
    ): IncidentCommunication {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $incident = Incident::query()->where('tenant_id', $tenantId)->where('id', $incidentId)->firstOrFail();

        if (strtoupper($audience) === 'PUBLIC' && ($incident->safety_restricted || $incident->severity === 'SEV_0')) {
            throw new \DomainException('safety_public_forbidden: SEV_0 or safety incidents MUST NOT be published to public status endpoints.');
        }

        /** @var IncidentCommunication */
        return IncidentCommunication::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'audience' => strtoupper($audience),
            'channel' => $channel,
            'recipient_ref' => $recipientRef,
            'message_summary' => $messageSummary,
            'approved_by_id' => $approvedById,
            'sent_at' => CarbonImmutable::now('UTC')->toDateTimeString(),
        ]);
    }

    public function transition(
        string $incidentId,
        string $targetStatus,
        string $actorId,
        string $reason,
        bool $actorHasSafetyPermission = false,
    ): Incident {
        $target = strtoupper($targetStatus);
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Transition reason cannot be empty.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        return (new Incident())->getConnection()->transaction(function () use (
            $tenantId,
            $incidentId,
            $target,
            $actorId,
            $reason,
            $actorHasSafetyPermission,
            $now
        ): Incident {
            $incident = Incident::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $incidentId)
                ->lockForUpdate()
                ->firstOrFail();

            IncidentLifecycle::assertCanTransition($incident->status, $target);

            if ($target === 'CLOSED') {
                $this->assertClosurePreconditions($tenantId, $incident, $actorHasSafetyPermission);
            }

            $updates = ['status' => $target];
            if ($target === 'CONTAINED' && $incident->contained_at === null) {
                $updates['contained_at'] = $now->toDateTimeString();
            }
            if ($target === 'RESOLVED' && $incident->resolved_at === null) {
                $updates['resolved_at'] = $now->toDateTimeString();
            }
            if ($target === 'CLOSED') {
                $updates['closed_at'] = $now->toDateTimeString();
            }

            $incident->forceFill($updates)->save();

            IncidentTimeline::query()->create([
                'tenant_id' => $tenantId,
                'incident_id' => $incidentId,
                'entry_type' => 'STATUS_' . $target,
                'actor_id' => $actorId,
                'summary' => "Transitioned to {$target}: {$reason}",
                'occurred_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
            ]);

            if ($target === 'CLOSED') {
                $this->audit->record(
                    'incident.record.closed',
                    ['module' => 'incident', 'resource_type' => 'incident', 'resource_id' => $incidentId],
                    null,
                    ['attributed_cause' => $incident->attributed_cause, 'actor_id' => $actorId]
                );
                $this->outbox->append(
                    $tenantId,
                    'incident',
                    $incidentId,
                    'incident.record.closed',
                    ['incident_id' => $incidentId, 'attributed_cause' => $incident->attributed_cause]
                );
            }

            return $incident;
        });
    }

    /**
     * Enforces §12.2 non-bypassable close preconditions:
     *  1. All affected bookings have a recorded concrete devotee_outcome (never PENDING/null)
     *  2. Attributed cause is non-UNKNOWN
     *  3. SEV_0 / SEV_1 requires completed postmortem
     *  4. SEV_0 / safety requires SAFETY_LEAD / safety permission
     */
    private function assertClosurePreconditions(string $tenantId, Incident $incident, bool $actorHasSafetyPermission): void
    {
        if ($incident->attributed_cause === 'UNKNOWN') {
            throw new \DomainException('closure_blocked_cause: Recorded attributed cause is required before closing an incident.');
        }

        $unresolvedImpacts = IncidentImpact::query()
            ->where('tenant_id', $tenantId)
            ->where('incident_id', (string) $incident->getKey())
            ->where(function ($q): void {
                $q->whereNull('devotee_outcome')->orWhere('devotee_outcome', 'PENDING');
            })
            ->exists();

        if ($unresolvedImpacts) {
            throw new \DomainException('closure_blocked_outcomes: Every affected booking must have a resolved devotee outcome before closing.');
        }

        if (in_array($incident->severity, ['SEV_0', 'SEV_1'], true)) {
            $hasPm = IncidentPostmortem::query()
                ->where('tenant_id', $tenantId)
                ->where('incident_id', (string) $incident->getKey())
                ->exists();

            if (! $hasPm) {
                throw new \DomainException('closure_blocked_postmortem: SEV_0 and SEV_1 incidents require a completed postmortem before closing.');
            }
        }

        if (($incident->safety_restricted || $incident->severity === 'SEV_0') && ! $actorHasSafetyPermission) {
            throw new \DomainException('closure_blocked_safety_lead: Safety incidents require authorized Safety Lead closure sign-off.');
        }
    }
}
