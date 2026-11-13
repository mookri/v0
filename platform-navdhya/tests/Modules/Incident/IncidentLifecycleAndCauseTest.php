<?php

declare(strict_types=1);

namespace Tests\Modules\Incident;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Feedback\Application\RecomputeFeedbackQualityService;
use App\Modules\Feedback\Application\SubmitFeedbackUseCase;
use App\Modules\Incident\Application\IncidentCorrelationService;
use App\Modules\Incident\Application\IncidentLifecycleService;
use App\Modules\Incident\Domain\OperationalAnomalyDetector;
use App\Modules\Incident\Models\IncidentCorrelation;
use App\Modules\Incident\Models\IncidentSeverityHistory;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class IncidentLifecycleAndCauseTest extends TenantIntegrationTestCase
{
    public function test_incident_closure_enforces_outcomes_cause_postmortem_and_logs_severity_downgrade(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$commander] = $this->actingAsMembership($tenant, [
            'incident:record:create',
            'incident:record:command',
            'incident:record:close',
        ]);

        $this->resolveContext($tenant);
        try {
            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_CONFIRMED,
            ]);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        // Declare SEV_1 incident with UNKNOWN cause & PENDING outcome
        $created = $this->actingAs($commander)
            ->postJson('/api/v1/incident/incidents', [
                'type' => 'STREAMING_DISRUPTION',
                'severity' => 'SEV_1',
                'title' => 'Darshan stream RTMP drop',
                'attributed_cause' => 'UNKNOWN',
                'impacts' => [
                    ['booking_id' => (string) $booking->getKey(), 'devotee_outcome' => 'PENDING'],
                ],
            ], $headers)
            ->assertCreated();

        $incidentId = (string) $created->json('data.id');
        $this->assertStringStartsWith('NV-INC-', (string) $created->json('data.incident_reference'));

        // Log severity downgrade audit row
        $this->actingAs($commander)
            ->postJson("/api/v1/incident/incidents/{$incidentId}/severity", [
                'severity' => 'SEV_2',
                'reason' => 'Downgrading briefly to inspect edge node metrics.',
            ], $headers)
            ->assertOk();

        // Escalate back to SEV_1
        $this->actingAs($commander)
            ->postJson("/api/v1/incident/incidents/{$incidentId}/severity", [
                'severity' => 'SEV_1',
                'reason' => 'Confirmed regional CDN disruption.',
            ], $headers)
            ->assertOk();

        $this->resolveContext($tenant);
        try {
            $this->assertTrue(IncidentSeverityHistory::query()->where('incident_id', $incidentId)->where('is_downgrade', true)->exists());
        } finally {
            $this->clearContext();
        }

        // Transition DECLARED -> MITIGATING -> MONITORING -> RESOLVED
        foreach (['MITIGATING', 'MONITORING', 'RESOLVED'] as $step) {
            $this->actingAs($commander)
                ->postJson("/api/v1/incident/incidents/{$incidentId}/transition", [
                    'status' => $step,
                    'reason' => "Stepping to {$step}",
                ], $headers)
                ->assertOk();
        }

        // Attempt CLOSED with UNKNOWN cause -> 409 blocked
        $this->actingAs($commander)
            ->postJson("/api/v1/incident/incidents/{$incidentId}/transition", [
                'status' => 'CLOSED',
                'reason' => 'Trying early close',
            ], $headers)
            ->assertStatus(409);

        // Record concrete cause + devotee outcome, STILL blocked for SEV_1 without postmortem
        $this->actingAs($commander)
            ->postJson("/api/v1/incident/incidents/{$incidentId}/impact-outcome", [
                'booking_id' => (string) $booking->getKey(),
                'attributed_cause' => 'PLATFORM',
                'devotee_outcome' => 'RESCHEDULED_WITH_WAIVER',
            ], $headers)
            ->assertOk();

        $this->actingAs($commander)
            ->postJson("/api/v1/incident/incidents/{$incidentId}/transition", [
                'status' => 'CLOSED',
                'reason' => 'Postmortem still missing',
            ], $headers)
            ->assertStatus(409);

        // Record postmortem -> closure succeeds
        $this->actingAs($commander)
            ->postJson("/api/v1/incident/incidents/{$incidentId}/postmortem", [
                'summary' => 'Edge ingest certificate rotation dropped live ceremony stream.',
                'root_cause_notes' => 'Automated cert renewal hook missed RTMP worker reload.',
            ], $headers)
            ->assertCreated();

        $this->actingAs($commander)
            ->postJson("/api/v1/incident/incidents/{$incidentId}/transition", [
                'status' => 'CLOSED',
                'reason' => 'All closure preconditions verified.',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'CLOSED');
    }

    public function test_correlations_are_scored_human_confirmed_and_revocable_without_auto_merge(): void
    {
        $tenant = $this->makeTenant();
        [$commander] = $this->actingAsMembership($tenant, ['incident:record:command', 'incident:record:create']);

        $this->resolveContext($tenant);
        try {
            $lifecycle = app(IncidentLifecycleService::class);
            $inc = $lifecycle->declareIncident([
                'type' => 'TEMPLE_ACCESS_DENIAL',
                'severity' => 'SEV_2',
                'title' => 'Main gate closed',
                'actor_id' => (string) $commander->id,
            ]);

            $detector = app(OperationalAnomalyDetector::class);
            $count = $detector->proposeClusterCorrelations((string) $tenant->getKey(), (string) $inc->getKey(), [
                ['booking_id' => (string) Str::ulid(), 'cluster_key' => 'venue:kashi-east-gate'],
                ['booking_id' => (string) Str::ulid(), 'cluster_key' => 'venue:kashi-east-gate'],
            ]);
            $this->assertSame(2, $count);

            $corr = IncidentCorrelation::query()->where('incident_id', (string) $inc->getKey())->firstOrFail();
            $this->assertSame(IncidentCorrelation::STATUS_PROPOSED, $corr->status);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        $this->actingAs($commander)
            ->postJson("/api/v1/incident/correlations/{$corr->getKey()}/decide", [
                'decision' => 'confirm',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', IncidentCorrelation::STATUS_CONFIRMED);

        $this->actingAs($commander)
            ->postJson("/api/v1/incident/correlations/{$corr->getKey()}/decide", [
                'decision' => 'revoke',
                'reason' => 'Second booking was scheduled at a different gate.',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', IncidentCorrelation::STATUS_REVOKED);
    }

    public function test_temple_access_denial_attributes_cause_to_venue_and_excludes_pandit_reliability_penalty(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$ops] = $this->actingAsMembership($tenant, ['incident:record:create', 'incident:record:command']);

        $this->resolveContext($tenant);
        try {
            $pandit = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE]);
            $goodBooking = Booking::factory()->create(['user_id' => $devotee->id, 'status' => BookingLifecycle::STATUS_COMPLETED]);
            $venueDeniedBooking = Booking::factory()->create(['user_id' => $devotee->id, 'status' => BookingLifecycle::STATUS_COMPLETED]);

            foreach ([$goodBooking, $venueDeniedBooking] as $b) {
                PanditAssignment::factory()->create([
                    'tenant_id' => (string) $tenant->getKey(),
                    'pandit_id' => (string) $pandit->getKey(),
                    'booking_id' => (string) $b->getKey(),
                    'status' => PanditAssignment::STATUS_COMPLETED,
                    'assigned_at' => now()->subDay(),
                    'confirmed_at' => now()->subDay(),
                ]);
            }

            $submitter = app(SubmitFeedbackUseCase::class);
            $submitter->execute(['booking_id' => (string) $goodBooking->getKey(), 'acting_user_id' => $devotee->id, 'overall_rating' => 5, 'share_publicly' => true]);
            $submitter->execute(['booking_id' => (string) $venueDeniedBooking->getKey(), 'acting_user_id' => $devotee->id, 'overall_rating' => 1, 'comment' => 'Temple gate locked by trustees', 'share_publicly' => true]);
        } finally {
            $this->clearContext();
        }

        // Record temple access denial via API -> attributed strictly to VENUE
        $this->actingAs($ops)
            ->postJson('/api/v1/incident/incidents/temple-denial', [
                'booking_id' => (string) $venueDeniedBooking->getKey(),
                'temple_ref' => 'KASHI-GARBHA-01',
                'reason' => 'Temple closed early for VIP protocol.',
                'pandit_id' => (string) $pandit->getKey(),
            ], $this->tenantHeaders($tenant))
            ->assertCreated()
            ->assertJsonPath('data.attributed_cause', 'VENUE');

        $this->resolveContext($tenant);
        try {
            $agg = app(RecomputeFeedbackQualityService::class)->recomputeForSubject('pandit', (string) $pandit->getKey());
            // Only the unaffected 5-star booking counts toward the pandit's aggregate (venue denial excluded per §6.3)
            $this->assertSame(1, $agg->sample_n);
            $this->assertEqualsWithDelta(5.0, (float) $agg->mean_average, 0.01);
        } finally {
            $this->clearContext();
        }
    }
}

