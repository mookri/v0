<?php

declare(strict_types=1);

namespace Tests\Modules\Incident;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Incident\Models\IncidentInterimRestriction;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Pandit\Models\PanditServiceEligibility;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class ReplacementRecoveryTest extends TenantIntegrationTestCase
{
    public function test_emergency_unavailability_enforces_medical_privacy_bounds_and_proposes_ranked_replacements(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$panditUser] = $this->actingAsMembership($tenant);
        [$ops] = $this->actingAsMembership($tenant, ['incident:recovery:recover', 'incident:record:create']);

        $scheduledAt = CarbonImmutable::now('UTC')->addDay();

        $this->resolveContext($tenant);
        try {
            $originalPandit = PanditProfile::factory()->create([
                'user_id' => $panditUser->id,
                'status' => PanditProfile::STATUS_ACTIVE,
                'verification_status' => 'VERIFIED',
                'tradition' => 'SMARTHA',
                'specializations' => ['PUJA-LAKSHMI'],
            ]);
            $sameTraditionStandby = PanditProfile::factory()->create([
                'status' => PanditProfile::STATUS_ACTIVE,
                'verification_status' => 'VERIFIED',
                'tradition' => 'SMARTHA',
                'specializations' => ['PUJA-LAKSHMI'],
            ]);
            $crossTraditionStandby = PanditProfile::factory()->create([
                'status' => PanditProfile::STATUS_ACTIVE,
                'verification_status' => 'VERIFIED',
                'tradition' => 'VAISHNAVA',
                'specializations' => ['PUJA-LAKSHMI'],
            ]);
            $restrictedStandby = PanditProfile::factory()->create([
                'status' => PanditProfile::STATUS_ACTIVE,
                'verification_status' => 'VERIFIED',
                'tradition' => 'SMARTHA',
                'specializations' => ['PUJA-LAKSHMI'],
            ]);
            foreach ([$sameTraditionStandby, $crossTraditionStandby, $restrictedStandby] as $cand) {
                PanditServiceEligibility::query()->create([
                    'tenant_id' => (string) $tenant->getKey(),
                    'pandit_id' => (string) $cand->getKey(),
                    'service_sku' => 'PUJA-LAKSHMI',
                    'is_qualified' => true,
                ]);
            }
            IncidentInterimRestriction::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'subject_type' => 'pandit',
                'subject_id' => (string) $restrictedStandby->getKey(),
                'restriction_kind' => 'ASSIGNMENT_PAUSE',
                'reason' => 'Active safety restriction',
                'proportionality_note' => 'Interim pause',
                'requested_by_id' => 'ops-1',
                'approved_by_safety_lead_id' => 'safety-1',
                'expires_at' => CarbonImmutable::now('UTC')->addDays(7)->toDateTimeString(),
            ]);

            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'service_sku' => 'PUJA-LAKSHMI',
                'status' => BookingLifecycle::STATUS_ASSIGNED,
                'scheduled_at' => $scheduledAt->toDateTimeString(),
            ]);
            PanditAssignment::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $originalPandit->getKey(),
                'booking_id' => (string) $booking->getKey(),
                'status' => PanditAssignment::STATUS_CONFIRMED,
                'assigned_at' => now(),
                'confirmed_at' => now(),
            ]);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        // Forbidden medical payload rejected cleanly (422)
        $this->actingAs($panditUser)
            ->postJson('/api/v1/incident/emergency-unavailability', [
                'booking_id' => (string) $booking->getKey(),
                'reason_category' => 'ILLNESS',
                'diagnosis' => 'High fever medical diagnosis details',
            ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'privacy_medical_forbidden');

        // Clean bounded reason category succeeds
        $inc = $this->actingAs($panditUser)
            ->postJson('/api/v1/incident/emergency-unavailability', [
                'booking_id' => (string) $booking->getKey(),
                'reason_category' => 'FAMILY_EMERGENCY',
                'pandit_id' => (string) $originalPandit->getKey(),
            ], $headers)
            ->assertCreated();

        $incidentId = (string) $inc->json('data.incident_id');

        // Propose replacements preferring 'SMARTHA'
        $proposals = $this->actingAs($ops)
            ->postJson("/api/v1/incident/incidents/{$incidentId}/replacements/propose", [
                'booking_id' => (string) $booking->getKey(),
                'preferred_tradition' => 'SMARTHA',
            ], $headers)
            ->assertCreated()
            ->json('data');

        $candidateIds = array_column($proposals, 'candidate_pandit_id');
        // Restricted pandit NEVER offered
        $this->assertNotContains((string) $restrictedStandby->getKey(), $candidateIds);
        // Same-tradition candidate ranked first with FULL_GATES; cross-tradition disclosed as PARTIAL_GATES
        $this->assertSame((string) $sameTraditionStandby->getKey(), $proposals[0]['candidate_pandit_id']);
        $this->assertSame('FULL_GATES', $proposals[0]['gate_disclosure_status']);
        $this->assertSame('PARTIAL_GATES', $proposals[1]['gate_disclosure_status']);

        $topReplacementId = (string) $proposals[0]['id'];

        // Reserve offer hold -> succeeds once
        $this->actingAs($ops)
            ->postJson("/api/v1/incident/replacements/{$topReplacementId}/reserve", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'RESERVED_HOLD');

        // Re-proposing for a second concurrent incident MUST exclude sameTraditionStandby while held (Scenario F)
        $inc2 = $this->actingAs($ops)
            ->postJson('/api/v1/incident/incidents', [
                'type' => 'PANDIT_NO_SHOW',
                'severity' => 'SEV_1',
                'title' => 'Concurrent disruption',
            ], $headers)
            ->assertCreated();

        $proposals2 = $this->actingAs($ops)
            ->postJson("/api/v1/incident/incidents/{$inc2->json('data.id')}/replacements/propose", [
                'booking_id' => (string) $booking->getKey(),
                'preferred_tradition' => 'SMARTHA',
            ], $headers)
            ->assertCreated()
            ->json('data');

        $this->assertNotContains((string) $sameTraditionStandby->getKey(), array_column($proposals2, 'candidate_pandit_id'));

        // Confirm WITHOUT devotee consent MUST fail closed (422 no silent substitution)
        $this->actingAs($ops)
            ->postJson("/api/v1/incident/replacements/{$topReplacementId}/confirm", [
                'devotee_consented' => false,
            ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'devotee_consent_required');

        // Confirm WITH explicit devotee consent reassigns atomically via Pandit Contract
        $this->actingAs($ops)
            ->postJson("/api/v1/incident/replacements/{$topReplacementId}/confirm", [
                'devotee_consented' => true,
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'CONFIRMED');

        $this->resolveContext($tenant);
        try {
            $newAssign = PanditAssignment::query()
                ->where('booking_id', (string) $booking->getKey())
                ->where('status', PanditAssignment::STATUS_CONFIRMED)
                ->firstOrFail();
            $this->assertSame((string) $sameTraditionStandby->getKey(), $newAssign->pandit_id);
        } finally {
            $this->clearContext();
        }
    }

    public function test_zero_eligible_candidates_records_exhausted_reschedule_or_refund_fallback(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$ops] = $this->actingAsMembership($tenant, ['incident:recovery:recover', 'incident:record:create']);

        $this->resolveContext($tenant);
        try {
            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'service_sku' => 'RARE-SOMAYAGA-99',
                'status' => BookingLifecycle::STATUS_CONFIRMED,
                'scheduled_at' => CarbonImmutable::now('UTC')->addDay()->toDateTimeString(),
            ]);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);
        $inc = $this->actingAs($ops)
            ->postJson('/api/v1/incident/incidents', [
                'type' => 'PANDIT_NO_SHOW',
                'severity' => 'SEV_1',
                'title' => 'No pandit available for specialized ceremony',
            ], $headers)
            ->assertCreated();

        $res = $this->actingAs($ops)
            ->postJson("/api/v1/incident/incidents/{$inc->json('data.id')}/replacements/propose", [
                'booking_id' => (string) $booking->getKey(),
            ], $headers)
            ->assertCreated();

        $this->assertSame('EXHAUSTED', $res->json('data.0.status'));
        $this->assertSame('NO_ELIGIBLE_CANDIDATE', $res->json('data.0.gate_disclosure_status'));
        $this->assertSame('RESCHEDULE_OR_FULL_REFUND', $res->json('data.0.fallback_action'));
    }
}
