<?php

declare(strict_types=1);

namespace App\Modules\Incident\Application;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Incident\Domain\ReplacementEligibilityEvaluator;
use App\Modules\Incident\Models\IncidentImpact;
use App\Modules\Incident\Models\IncidentReplacement;
use App\Modules\Incident\Models\IncidentTimeline;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Replacement Proposal, Concurrent Offer Hold, & Devotee-Consented Confirmation (E-TS-06 / TS-13).
 */
final class ProposeAndConfirmReplacementUseCase
{
    public function __construct(
        private readonly ReplacementEligibilityEvaluator $evaluator,
        private readonly ReadsBookings $bookings,
        private readonly ManagesPanditAssignments $assignments,
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    /**
     * @return list<IncidentReplacement>
     */
    public function propose(
        string $incidentId,
        string $bookingId,
        ?string $preferredTradition = null,
        ?string $preferredLanguage = null,
    ): array {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $window = $this->bookings->windowsByIds([$bookingId])[$bookingId] ?? null;
        if ($window === null) {
            throw new \RuntimeException('not_found: Booking not found.');
        }

        $currentAssignment = $this->assignments->assignmentForBooking($bookingId);
        $originalPanditId = $currentAssignment['pandit_id'] ?? null;

        $startIso = (string) ($window['scheduled_at'] ?? CarbonImmutable::now('UTC')->addDay()->toJSON());
        $duration = max(30, (int) ($window['duration_minutes'] ?? 60));
        $endIso = CarbonImmutable::parse($startIso)->addMinutes($duration)->toJSON();

        $ranked = $this->evaluator->rankEligibleCandidates(
            $tenantId,
            (string) ($window['service_sku'] ?? ''),
            $startIso,
            $endIso,
            $originalPanditId,
            $preferredTradition,
            $preferredLanguage,
        );

        $now = CarbonImmutable::now('UTC');

        if ($ranked === []) {
            /** @var IncidentReplacement $exhausted */
            $exhausted = IncidentReplacement::query()->create([
                'tenant_id' => $tenantId,
                'incident_id' => $incidentId,
                'booking_id' => $bookingId,
                'original_pandit_id' => $originalPanditId,
                'candidate_pandit_id' => null,
                'score' => null,
                'eligibility_gates_passed' => [],
                'gate_disclosure_status' => 'NO_ELIGIBLE_CANDIDATE',
                'status' => 'EXHAUSTED',
                'fallback_action' => 'RESCHEDULE_OR_FULL_REFUND',
            ]);

            IncidentTimeline::query()->create([
                'tenant_id' => $tenantId,
                'incident_id' => $incidentId,
                'entry_type' => 'REPLACEMENT_EXHAUSTED',
                'actor_id' => 'replacement_engine',
                'summary' => 'No eligible verified standby pandit found; falling back to devotee reschedule or full refund.',
                'occurred_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
            ]);

            return [$exhausted];
        }

        $created = [];
        foreach (array_slice($ranked, 0, 3) as $candidate) {
            /** @var IncidentReplacement $rec */
            $rec = IncidentReplacement::query()->create([
                'tenant_id' => $tenantId,
                'incident_id' => $incidentId,
                'booking_id' => $bookingId,
                'original_pandit_id' => $originalPanditId,
                'candidate_pandit_id' => $candidate['pandit_id'],
                'score' => $candidate['score'],
                'eligibility_gates_passed' => $candidate['eligibility_gates_passed'],
                'gate_disclosure_status' => $candidate['gate_disclosure_status'],
                'status' => 'PROPOSED',
            ]);
            $created[] = $rec;
        }

        return $created;
    }

    /**
     * Atomically reserve a replacement candidate offer, blocking concurrent
     * emergencies from offering the same standby pandit simultaneously (Scenario F).
     */
    public function reserveHold(string $replacementId, int $holdMinutes = 15): IncidentReplacement
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        return (new IncidentReplacement())->getConnection()->transaction(function () use (
            $tenantId,
            $replacementId,
            $holdMinutes,
            $now
        ): IncidentReplacement {
            $replacement = IncidentReplacement::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $replacementId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($replacement->candidate_pandit_id === null || $replacement->status === 'EXHAUSTED') {
                throw new \DomainException('no_candidate: Exhausted replacement proposal has no candidate to reserve.');
            }

            $concurrentHold = IncidentReplacement::query()
                ->where('tenant_id', $tenantId)
                ->where('candidate_pandit_id', $replacement->candidate_pandit_id)
                ->where('id', '!=', $replacementId)
                ->where('status', 'RESERVED_HOLD')
                ->where('offer_expires_at', '>', $now->toDateTimeString())
                ->lockForUpdate()
                ->exists();

            if ($concurrentHold) {
                throw new \OverflowException('concurrent_hold_conflict: Standby pandit already holds an active replacement offer.');
            }

            $replacement->forceFill([
                'status' => 'RESERVED_HOLD',
                'offer_expires_at' => $now->addMinutes(max(5, $holdMinutes))->toDateTimeString(),
            ])->save();

            return $replacement;
        });
    }

    /**
     * Finalize replacement assignment ONLY with explicit devotee consent (TS-13 — no silent substitution).
     */
    public function confirmWithDevoteeConsent(
        string $replacementId,
        string $actorId,
        bool $devoteeConsented,
        int $compensationMinor = 0,
    ): IncidentReplacement {
        if (! $devoteeConsented) {
            throw new \DomainException('devotee_consent_required: Devotee explicit consent is mandatory before assigning a replacement pandit.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        $replacement = IncidentReplacement::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $replacementId)
            ->firstOrFail();

        if ($replacement->candidate_pandit_id === null || $replacement->status === 'EXHAUSTED') {
            throw new \DomainException('no_candidate: Cannot confirm an exhausted replacement proposal.');
        }

        $this->assignments->reassignForIncident(
            $replacement->booking_id,
            $replacement->candidate_pandit_id,
            $compensationMinor,
        );

        $replacement->forceFill([
            'status' => 'CONFIRMED',
            'devotee_consented_at' => $now->toDateTimeString(),
            'confirmed_by_id' => $actorId,
        ])->save();

        IncidentImpact::query()
            ->where('tenant_id', $tenantId)
            ->where('incident_id', $replacement->incident_id)
            ->where('booking_id', $replacement->booking_id)
            ->update(['devotee_outcome' => 'REPLACED_WITH_CONSENT']);

        IncidentTimeline::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => $replacement->incident_id,
            'entry_type' => 'REPLACEMENT_CONFIRMED',
            'actor_id' => $actorId,
            'summary' => "Confirmed replacement pandit {$replacement->candidate_pandit_id} with explicit devotee consent.",
            'occurred_at' => $now->toDateTimeString(),
            'created_at' => $now->toDateTimeString(),
        ]);

        $this->audit->record(
            'incident.recovery.completed',
            ['module' => 'incident', 'resource_type' => 'incident_replacement', 'resource_id' => $replacementId],
            null,
            [
                'incident_id' => $replacement->incident_id,
                'booking_id' => $replacement->booking_id,
                'candidate_pandit_id' => $replacement->candidate_pandit_id,
                'gate_disclosure_status' => $replacement->gate_disclosure_status,
            ]
        );

        $this->outbox->append(
            $tenantId,
            'incident',
            $replacement->incident_id,
            'incident.recovery.completed',
            [
                'incident_id' => $replacement->incident_id,
                'booking_id' => $replacement->booking_id,
                'replacement_pandit_id' => $replacement->candidate_pandit_id,
                'gate_disclosure_status' => $replacement->gate_disclosure_status,
            ]
        );

        return $replacement;
    }
}

