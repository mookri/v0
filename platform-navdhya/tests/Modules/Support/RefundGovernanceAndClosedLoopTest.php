<?php

declare(strict_types=1);

namespace Tests\Modules\Support;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Feedback\Application\SubmitFeedbackUseCase;
use App\Modules\Incident\Application\IncidentLifecycleService;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Support\Domain\Enums\CaseCategory;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\CaseType;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Platform\Outbox\OutboxEvent;
use Tests\TenantIntegrationTestCase;

final class RefundGovernanceAndClosedLoopTest extends TenantIntegrationTestCase
{
    public function test_refund_blocks_forced_credit_emits_finance_outbox_and_builds_closed_loop_dossier(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$staff] = $this->actingAsMembership($tenant, [
            'support:case:view',
            'support:case:manage',
            'support:case:resolve',
            'support:refund:request',
            'support:appeal:decide',
            'support:improvement:manage',
        ]);
        [$independentReviewer] = $this->actingAsMembership($tenant, ['support:appeal:decide']);

        $this->resolveContext($tenant);
        try {
            $pandit = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE]);
            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_COMPLETED,
            ]);
            PanditAssignment::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $pandit->getKey(),
                'booking_id' => (string) $booking->getKey(),
                'status' => PanditAssignment::STATUS_COMPLETED,
                'assigned_at' => now()->subDay(),
                'confirmed_at' => now()->subDay(),
            ]);

            app(SubmitFeedbackUseCase::class)->execute([
                'booking_id' => (string) $booking->getKey(),
                'acting_user_id' => $devotee->id,
                'overall_rating' => 2,
                'comment' => 'Streaming dropped repeatedly',
                'share_publicly' => true,
            ]);

            $inc = app(IncidentLifecycleService::class);
            $incId = $inc->openForBooking((string) $booking->getKey(), 'STREAMING_DISRUPTION', 'SEV_2', 'Live stream disruption', 'PLATFORM');
            $inc->addPreventionTask($incId, 'Add automated RTMP failover probe', 'MONITORING', (string) $staff->id);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        $caseRes = $this->actingAs($devotee)
            ->postJson('/api/v1/support/cases', [
                'subject' => 'Stream disruption during puja',
                'body' => 'Our online stream dropped for 25 minutes.',
                'case_type' => CaseType::SERVICE_COMPLAINT->value,
                'category' => CaseCategory::STREAMING_FAILURE->value,
                'booking_id' => (string) $booking->getKey(),
            ], $headers)
            ->assertCreated();

        $caseId = (string) $caseRes->json('data.id');
        foreach ([CaseStatus::CLASSIFIED, CaseStatus::UNDER_INVESTIGATION] as $st) {
            $this->actingAs($staff)
                ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", ['status' => $st->value, 'reason' => 'Advancing'], $headers)
                ->assertOk();
        }

        // Forced credit without consent MUST fail closed (422)
        $this->actingAs($staff)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/refunds", [
                'booking_id' => (string) $booking->getKey(),
                'amount_minor' => 51000,
                'basis' => 'Pro-rata refund',
                'proposed_type' => 'PARTIAL',
                'remedy_instrument' => RemedyType::VOLUNTARY_CREDIT->value,
            ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'forced_credit_forbidden');

        // Monetary refund to original payment method succeeds & emits outbox boundary event
        $refund = $this->actingAs($staff)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/refunds", [
                'booking_id' => (string) $booking->getKey(),
                'amount_minor' => 51000,
                'basis' => 'Confirmed platform stream outage refund',
                'proposed_type' => 'PARTIAL',
                'remedy_instrument' => RemedyType::MONETARY_ORIGINAL_METHOD->value,
            ], $headers)
            ->assertCreated();

        $refundId = (string) $refund->json('data.id');
        $this->assertTrue(OutboxEvent::withoutGlobalScopes()->where('tenant_id', (string) $tenant->getKey())->where('event_type', 'support.refund.requested')->exists());

        $this->actingAs($staff)
            ->postJson("/api/v1/support/admin/refunds/{$refundId}/approve", [
                'finance_execution_reference' => 'FIN-RFD-99812',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.state', 'EXECUTED');

        // Closed-loop dossier links booking, review, incident + prevention task, and refund
        $dossier = $this->actingAs($staff)
            ->getJson("/api/v1/support/admin/cases/{$caseId}/dossier", $headers)
            ->assertOk();

        $this->assertNotNull($dossier->json('data.feedback_submission.review_reference'));
        $this->assertSame('PLATFORM', $dossier->json('data.incidents_and_prevention.0.attributed_cause'));
        $this->assertCount(1, $dossier->json('data.incidents_and_prevention.0.prevention_task_ids'));
        $this->assertSame('FIN-RFD-99812', $dossier->json('data.refund_requests.0.finance_execution_reference'));

        // Improvement plan blocks safety cases (422) and excludes external platform cause count cleanly
        $this->actingAs($staff)
            ->postJson('/api/v1/support/admin/improvement-plans', [
                'subject_type' => 'pandit',
                'subject_id' => (string) $pandit->getKey(),
                'trigger_pattern' => 'Harassment safety complaint',
                'incident_refs' => ['NV-INC-1'],
                'actions' => ['Coaching'],
                'involves_safety_case' => true,
            ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'safety_pip_forbidden');

        $plan = $this->actingAs($staff)
            ->postJson('/api/v1/support/admin/improvement-plans', [
                'subject_type' => 'pandit',
                'subject_id' => (string) $pandit->getKey(),
                'trigger_pattern' => 'Repeated arrival delay pattern',
                'incident_refs' => [$dossier->json('data.incidents_and_prevention.0.incident_reference')],
                'actions' => ['Dispatch 15m buffer reminder'],
                'booking_ids' => [(string) $booking->getKey()],
            ], $headers)
            ->assertCreated();
        $this->assertSame(1, $plan->json('data.excluded_external_causes_count'));

        // Independent appeal: resolve first, then original staff blocked (409), independent reviewer succeeds
        $this->actingAs($staff)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/resolve", [
                'remedy_type' => RemedyType::MONETARY_ORIGINAL_METHOD->value,
                'decision_summary' => 'Partial refund executed to original payment method.',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', CaseStatus::RESOLVED->value);

        $appeal = $this->actingAs($devotee)
            ->postJson("/api/v1/support/cases/{$caseId}/appeal", [
                'role' => 'devotee',
                'grounds' => 'Partial refund insufficient for lost muhurat window.',
            ], $headers)
            ->assertCreated();

        $appealId = (string) $appeal->json('data.id');

        $this->actingAs($staff)
            ->postJson("/api/v1/support/admin/appeals/{$appealId}/decide", [
                'overturn' => false,
                'notes' => 'Original staff cannot review their own resolution.',
            ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'independence_violation');

        $this->actingAs($independentReviewer)
            ->postJson("/api/v1/support/admin/appeals/{$appealId}/decide", [
                'overturn' => true,
                'notes' => 'Approving full muhurat disruption refund review.',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'OVERTURNED');
    }
}
