<?php

declare(strict_types=1);

namespace Tests\Feature\Trust;

use App\Domain\Trust\Parties\PartyEntitlement;
use App\Modules\Booking\Application\ConfirmBookingNoShowUseCase;
use App\Modules\Booking\Application\RecordPanditArrivalUseCase;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Feedback\Application\IssueFeedbackInvitationUseCase;
use App\Modules\Feedback\Application\RecomputeFeedbackQualityService;
use App\Modules\Feedback\Application\SubmitFeedbackUseCase;
use App\Modules\Feedback\Models\FeedbackInvitation;
use App\Modules\Incident\Application\IncidentCorrelationService;
use App\Modules\Incident\Application\IncidentLifecycleService;
use App\Modules\Incident\Application\ProposeAndConfirmReplacementUseCase;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Pandit\Models\PanditServiceEligibility;
use App\Modules\Support\Application\OpenSupportCaseUseCase;
use App\Modules\Support\Application\ProviderImprovementPlanService;
use App\Modules\Support\Application\ResolveSupportCaseUseCase;
use App\Modules\Support\Application\SafetyCaseService;
use App\Modules\Support\Application\SupportAppealService;
use App\Modules\Support\Application\SupportCaseTransitionService;
use App\Modules\Support\Application\SupportRefundService;
use App\Modules\Support\Domain\Enums\CaseCategory;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\CaseType;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Modules\Support\Domain\Grievance\StatutoryClockService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * High-volume Stress, Concurrency & Safety Invariance Suite (§22.2 A–L).
 */
final class StressAndSafetyInvarianceTest extends TenantIntegrationTestCase
{
    public function test_invariants_a_b_c_e_arrival_blocks_no_show_and_feedback_server_derived_subject(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);

        $this->resolveContext($tenant);
        try {
            $panditA = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE]);
            $panditB = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE]);

            $assignedBooking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_ASSIGNED,
                'scheduled_at' => CarbonImmutable::now('UTC')->subMinutes(30),
            ]);
            PanditAssignment::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $panditA->getKey(),
                'booking_id' => (string) $assignedBooking->getKey(),
                'status' => 'ASSIGNED',
            ]);

            // Invariant A: Recorded arrival MUST block NO_SHOW confirmation
            app(RecordPanditArrivalUseCase::class)->recordPrimary(
                (string) $assignedBooking->getKey(),
                (string) $panditA->getKey(),
            );

            $blockedNoShow = false;
            try {
                app(ConfirmBookingNoShowUseCase::class)->execute(
                    (string) $assignedBooking->getKey(),
                    'ops_1',
                    true,
                    'Late check attempt',
                );
            } catch (BookingStateForbiddenException) {
                $blockedNoShow = true;
            }
            $this->assertTrue($blockedNoShow, 'Invariant A: arrival evidence must block NO_SHOW transition.');

            // Invariant B & E: Completed ceremony derives panditA despite forged client_subject_id panditB,
            // and NEW_PROVIDER (<5 samples) stays capped in NEW_PROVIDER confidence tier.
            $completed = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_COMPLETED,
            ]);
            PanditAssignment::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $panditA->getKey(),
                'booking_id' => (string) $completed->getKey(),
                'status' => 'COMPLETED',
            ]);

            $forgedRejected = false;
            try {
                app(SubmitFeedbackUseCase::class)->execute([
                    'booking_id' => (string) $completed->getKey(),
                    'acting_user_id' => $devotee->id,
                    'client_subject_id' => (string) $panditB->getKey(),
                    'overall_rating' => 5,
                    'comment' => 'Great ceremony',
                    'share_publicly' => true,
                ]);
            } catch (\InvalidArgumentException $e) {
                $forgedRejected = str_contains($e->getMessage(), 'subject_mismatch');
            }
            $this->assertTrue($forgedRejected, 'Invariant B1: forged client_subject_id must reject with subject_mismatch.');

            $res = app(SubmitFeedbackUseCase::class)->execute([
                'booking_id' => (string) $completed->getKey(),
                'acting_user_id' => $devotee->id,
                'overall_rating' => 5,
                'comment' => 'Great ceremony',
                'share_publicly' => true,
            ]);

            $this->assertSame((string) $panditA->getKey(), $res['submission']->subject_id);

            $quality = app(RecomputeFeedbackQualityService::class)->recomputeForSubject('pandit', (string) $panditA->getKey());
            $this->assertSame('NEW_PROVIDER', $quality->confidence_tier);

            // Invariant C: Uncompleted booking rejects rating and directs to support_report
            $uncompleted = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_NO_SHOW,
            ]);
            $redirected = false;
            try {
                app(SubmitFeedbackUseCase::class)->execute([
                    'booking_id' => (string) $uncompleted->getKey(),
                    'acting_user_id' => $devotee->id,
                    'overall_rating' => 1,
                    'comment' => 'Pandit never arrived',
                    'share_publicly' => true,
                ]);
            } catch (\DomainException $e) {
                $redirected = str_contains($e->getMessage(), 'redirect_support_report');
            }
            $this->assertTrue($redirected, 'Invariant C: uncompleted bookings must redirect to support_report.');
        } finally {
            $this->clearContext();
        }
    }

    public function test_invariants_d_f_g_external_cause_closure_preconditions_and_human_correlation(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);

        $this->resolveContext($tenant);
        try {
            $pandit = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE]);
            $b1 = Booking::factory()->create(['user_id' => $devotee->id, 'status' => BookingLifecycle::STATUS_COMPLETED]);
            $b2 = Booking::factory()->create(['user_id' => $devotee->id, 'status' => BookingLifecycle::STATUS_COMPLETED]);
            PanditAssignment::factory()->create(['tenant_id' => (string) $tenant->getKey(), 'pandit_id' => (string) $pandit->getKey(), 'booking_id' => (string) $b1->getKey()]);
            PanditAssignment::factory()->create(['tenant_id' => (string) $tenant->getKey(), 'pandit_id' => (string) $pandit->getKey(), 'booking_id' => (string) $b2->getKey()]);

            $incSvc = app(IncidentLifecycleService::class);
            $inc1 = $incSvc->declareIncident([
                'type' => 'TEMPLE_ACCESS_DENIAL',
                'severity' => 'SEV_1',
                'title' => 'Gate locked by trust',
                'attributed_cause' => 'VENUE',
                'actor_id' => 'ops_1',
                'impacts' => [
                    ['booking_id' => (string) $b1->getKey(), 'attributed_cause' => 'VENUE', 'devotee_outcome' => 'RESCHEDULED'],
                ],
            ]);

            // Invariant D: VENUE cause excluded from PIP negative counts
            $plan = app(ProviderImprovementPlanService::class)->propose(
                'pandit',
                (string) $pandit->getKey(),
                'Venue outage check',
                [$inc1->incident_reference],
                ['Review alternate mandapam'],
                [(string) $b1->getKey()],
            );
            $this->assertSame(1, $plan->excluded_external_causes_count);

            // Invariant F: SEV_1 closure fails without postmortem & prevention task
            $incSvc->transition((string) $inc1->getKey(), 'CONTAINED', 'ops_1', 'Contained');
            $incSvc->transition((string) $inc1->getKey(), 'MONITORING', 'ops_1', 'Monitoring');
            $incSvc->transition((string) $inc1->getKey(), 'RESOLVED', 'ops_1', 'Resolved');

            $blockedClose = false;
            try {
                $incSvc->transition((string) $inc1->getKey(), 'CLOSED', 'ops_1', 'Attempt early close');
            } catch (\DomainException $e) {
                $blockedClose = str_contains($e->getMessage(), 'postmortem');
            }
            $this->assertTrue($blockedClose, 'Invariant F: SEV_1 closure must block without postmortem.');

            // Invariant G: Correlations stay PROPOSED until human commander confirms
            $corrSvc = app(IncidentCorrelationService::class);
            $corr = $corrSvc->propose(
                (string) $inc1->getKey(),
                'same_pandit_window',
                0.88,
                ['shared_pandit' => (string) $pandit->getKey()],
                (string) $b2->getKey(),
            );
            $this->assertSame('PROPOSED', $corr->status);
            $confirmed = $corrSvc->confirm($corr->getKey(), 'cmd_1');
            $this->assertSame('CONFIRMED', $confirmed->status);
            $revoked = $corrSvc->revoke($corr->getKey(), 'cmd_1', 'Unrelated ceremony');
            $this->assertSame('REVOKED', $revoked->status);
        } finally {
            $this->clearContext();
        }
    }

    public function test_invariants_h_i_j_k_l_concurrent_replacement_statutory_safety_refund_and_appeal(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);

        // Invariant I: Statutory calendar month clamping (Jan 31 -> Feb 28/29)
        $clock = app(StatutoryClockService::class);
        $this->assertSame(
            '2026-02-28',
            $clock->computeDueAt((string) $tenant->getKey(), 'redress', CarbonImmutable::parse('2026-01-31 12:00:00', 'UTC'))['due_at']->toDateString(),
        );
        $this->assertSame(
            '2024-02-29',
            $clock->computeDueAt((string) $tenant->getKey(), 'redress', CarbonImmutable::parse('2024-01-31 12:00:00', 'UTC'))['due_at']->toDateString(),
        );

        $this->resolveContext($tenant);
        try {
            $origPandit = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE, 'verification_status' => 'VERIFIED', 'tradition' => 'SMARTHA']);
            $standbyPandit = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE, 'verification_status' => 'VERIFIED', 'tradition' => 'SMARTHA']);
            PanditServiceEligibility::create(['tenant_id' => (string) $tenant->getKey(), 'pandit_id' => (string) $standbyPandit->getKey(), 'service_sku' => 'SKU-RUDRA-STRESS', 'is_qualified' => true]);

            $start = CarbonImmutable::now('UTC')->addDay()->setTime(9, 0);
            $b1 = Booking::factory()->create(['user_id' => $devotee->id, 'service_sku' => 'SKU-RUDRA-STRESS', 'scheduled_at' => $start, 'duration_minutes' => 60, 'status' => BookingLifecycle::STATUS_ASSIGNED]);
            $b2 = Booking::factory()->create(['user_id' => $devotee->id, 'service_sku' => 'SKU-RUDRA-STRESS', 'scheduled_at' => $start, 'duration_minutes' => 60, 'status' => BookingLifecycle::STATUS_ASSIGNED]);
            PanditAssignment::factory()->create(['tenant_id' => (string) $tenant->getKey(), 'pandit_id' => (string) $origPandit->getKey(), 'booking_id' => (string) $b1->getKey(), 'status' => 'ASSIGNED']);

            $incId = app(IncidentLifecycleService::class)->openForBooking((string) $b1->getKey(), 'EMERGENCY_UNAVAILABILITY', 'SEV_2', 'Emergency replacement', 'PROVIDER');
            $replSvc = app(ProposeAndConfirmReplacementUseCase::class);

            // Proposal + atomic reserveHold locks standbyPandit on b1
            $proposals = $replSvc->propose($incId, (string) $b1->getKey(), 'SMARTHA');
            $firstId = (string) $proposals[0]->getKey();
            $replSvc->reserveHold($firstId);

            // Invariant H1: Consent mandatory before confirm
            $consentBlocked = false;
            try {
                $replSvc->confirmWithDevoteeConsent($firstId, 'ops_1', false);
            } catch (\DomainException $e) {
                $consentBlocked = str_contains($e->getMessage(), 'devotee_consent_required');
            }
            $this->assertTrue($consentBlocked);

            // Invariant H2: Concurrent proposal for overlapping b2 excludes standbyPandit due to active RESERVED_HOLD
            $proposalsB2 = $replSvc->propose($incId, (string) $b2->getKey(), 'SMARTHA');
            $this->assertSame('EXHAUSTED', $proposalsB2[0]->status);

            // Confirm b1 cleanly with explicit consent
            $replSvc->confirmWithDevoteeConsent($firstId, 'ops_1', true);
            $this->assertSame((string) $standbyPandit->getKey(), app(ManagesPanditAssignments::class)->assignmentForBooking((string) $b1->getKey())['pandit_id']);

            // Invariant J: Safety case encrypts REPORTER_PRIVATE, blocks mediation, and suppresses feedback invitation
            $safetyCase = app(OpenSupportCaseUseCase::class)->execute([
                'reporter_user_id' => $devotee->id,
                'subject' => 'Harassment incident report',
                'body' => 'Private safety report details',
                'case_type' => CaseType::SAFETY_REPORT,
                'category' => CaseCategory::HARASSMENT_SAFETY,
                'booking_id' => (string) $b1->getKey(),
            ]);
            app(SafetyCaseService::class)->addConfidentialMessage((string) $safetyCase->getKey(), (string) $devotee->id, 'devotee', 'Sensitive reporter-only testimony');
            $rawBody = (string) DB::connection('tenant_shared')->table('support_case_messages')->where('case_id', $safetyCase->getKey())->where('visibility_scope', PartyEntitlement::SCOPE_REPORTER_PRIVATE)->value('body');
            $this->assertStringNotContainsString('Sensitive reporter-only testimony', $rawBody);

            app(IssueFeedbackInvitationUseCase::class)->suppressForSafety((string) $b1->getKey(), (string) $safetyCase->getKey());
            $this->assertSame(0, FeedbackInvitation::query()->where('booking_id', (string) $b1->getKey())->whereIn('state', [FeedbackInvitation::STATE_PENDING, FeedbackInvitation::STATE_SENT])->count());

            $mediationBlocked = false;
            try {
                app(ResolveSupportCaseUseCase::class)->resolve((string) $safetyCase->getKey(), RemedyType::EXPLANATION_ONLY, 'Apology only', 'safety_lead');
            } catch (\InvalidArgumentException $e) {
                $mediationBlocked = str_contains($e->getMessage(), 'safety_no_mediation_forbidden');
            }
            $this->assertTrue($mediationBlocked);

            // Invariant K & L: Normal complaint forced credit blocked + two-way independent appeal enforced
            $complaint = app(OpenSupportCaseUseCase::class)->execute([
                'reporter_user_id' => $devotee->id,
                'subject' => 'Late arrival refund',
                'body' => 'Requested monetary refund',
                'case_type' => CaseType::SERVICE_COMPLAINT,
                'category' => CaseCategory::BILLING_REFUND,
                'booking_id' => (string) $b2->getKey(),
            ]);
            app(SupportCaseTransitionService::class)->transition((string) $complaint->getKey(), CaseStatus::CLASSIFIED, 'staff_1', 'staff', 'triage', 'Classify');
            app(SupportCaseTransitionService::class)->transition((string) $complaint->getKey(), CaseStatus::UNDER_INVESTIGATION, 'staff_1', 'staff', 'investigate', 'Investigate');

            $forcedCreditBlocked = false;
            try {
                app(SupportRefundService::class)->requestRefund((string) $complaint->getKey(), (string) $b2->getKey(), 25000, 'Delay refund', 'PARTIAL', RemedyType::VOLUNTARY_CREDIT->value, 'staff_1');
            } catch (\InvalidArgumentException $e) {
                $forcedCreditBlocked = str_contains($e->getMessage(), 'forced_credit_forbidden');
            }
            $this->assertTrue($forcedCreditBlocked);

            app(SupportCaseTransitionService::class)->transition((string) $complaint->getKey(), CaseStatus::RESOLUTION_PROPOSED, 'staff_1', 'staff', 'propose', 'Propose monetary refund');
            app(ResolveSupportCaseUseCase::class)->resolve((string) $complaint->getKey(), RemedyType::MONETARY_ORIGINAL_METHOD, 'Partial monetary refund approved', 'staff_1');
            $appeal = app(SupportAppealService::class)->fileAppeal((string) $complaint->getKey(), (string) $devotee->id, 'devotee', 'Disputing partial amount calculation');

            $selfReviewBlocked = false;
            try {
                app(SupportAppealService::class)->decideAppeal((string) $appeal->getKey(), 'staff_1', false, 'Trying to self-affirm');
            } catch (\DomainException $e) {
                $selfReviewBlocked = str_contains($e->getMessage(), 'independence_violation');
            }
            $this->assertTrue($selfReviewBlocked);

            $decided = app(SupportAppealService::class)->decideAppeal((string) $appeal->getKey(), 'reviewer_indep', true, 'Independent escalation approved');
            $this->assertSame('OVERTURNED', $decided->status);
        } finally {
            $this->clearContext();
        }
    }
}
