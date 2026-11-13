<?php

declare(strict_types=1);

namespace Tests\TenantIsolation;

use App\Modules\Booking\Models\PanditArrival;
use App\Modules\Feedback\Models\FeedbackScoreSnapshot;
use App\Modules\Feedback\Models\FeedbackSubjectAggregate;
use App\Modules\Incident\Models\Incident;
use App\Modules\Incident\Models\IncidentCorrelation;
use App\Modules\Incident\Models\IncidentImpact;
use App\Modules\Incident\Models\IncidentInterimRestriction;
use App\Modules\Incident\Models\IncidentPostmortem;
use App\Modules\Incident\Models\IncidentPreventionTask;
use App\Modules\Incident\Models\IncidentReplacement;
use App\Modules\Incident\Models\IncidentRole;
use App\Modules\Support\Domain\Enums\CaseCategory;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\CaseType;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Modules\Support\Models\ProviderImprovementPlan;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseAppeal;
use App\Modules\Support\Models\SupportCaseEvidence;
use App\Modules\Support\Models\SupportCaseMessage;
use App\Modules\Support\Models\SupportCaseResolution;
use App\Modules\Support\Models\SupportCaseStatusHistory;
use App\Modules\Support\Models\SupportExternalReferral;
use App\Modules\Support\Models\SupportGrievance;
use App\Modules\Support\Models\SupportRefundRequest;
use App\Modules\Support\Models\TrustTaxonomy;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;
use Tests\TenantIsolation\Concerns\AssertTenantIsolation;

final class TrustIsolationMatrixTest extends TenantIntegrationTestCase
{
    use AssertTenantIsolation;

    public function test_pandit_arrivals_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            PanditArrival::class,
            fn (): array => [
                'booking_id' => (string) Str::ulid(),
                'pandit_id' => (string) Str::ulid(),
                'arrived_at' => now(),
                'recorded_by_actor_id' => 'user_1',
                'source_channel' => PanditArrival::SOURCE_PARTNER_APP,
                'dispute_state' => PanditArrival::DISPUTE_NONE,
            ],
            ['dispute_state' => PanditArrival::DISPUTE_CONTESTED],
        );
    }

    public function test_support_cases_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            SupportCase::class,
            fn (): array => [
                'case_reference' => 'NV-CASE-ISO-'.substr((string) Str::ulid(), -6),
                'reporter_user_id' => 1,
                'subject' => 'Isolation test case',
                'case_type' => CaseType::SERVICE_COMPLAINT->value,
                'category' => CaseCategory::BOOKING_SCHEDULE->value,
                'status' => CaseStatus::NEW->value,
            ],
            ['subject' => 'Updated subject'],
        );
    }

    public function test_support_case_messages_and_resolutions_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            SupportCaseMessage::class,
            fn (): array => [
                'case_id' => (string) Str::ulid(),
                'author_id' => '1',
                'author_role' => 'devotee',
                'visibility_scope' => 'PARTICIPANT',
                'body' => 'Message text',
            ],
            ['body' => 'Mutated message'],
        );

        $this->assertTenantIsolationMatrix(
            SupportCaseResolution::class,
            fn (): array => [
                'case_id' => (string) Str::ulid(),
                'remedy_type' => RemedyType::EXPLANATION_ONLY->value,
                'decision_summary' => 'Resolved',
                'decided_by_id' => '1',
                'proposed_by_id' => '1',
            ],
            ['decision_summary' => 'Mutated'],
        );

        $this->assertTenantIsolationMatrix(
            SupportCaseStatusHistory::class,
            fn (): array => [
                'case_id' => (string) Str::ulid(),
                'actor_id' => '1',
                'actor_type' => 'staff',
                'action' => 'triage',
                'previous_state' => CaseStatus::NEW->value,
                'new_state' => CaseStatus::TRIAGED->value,
                'reason' => 'Triaged',
                'policy_version' => 'v1',
                'correlation_id' => (string) Str::ulid(),
                'occurred_at' => now(),
            ],
            ['reason' => 'Mutated'],
        );

        $this->assertTenantIsolationMatrix(
            TrustTaxonomy::class,
            fn (): array => [
                'kind' => 'category',
                'code' => 'cat_'.Str::lower((string) Str::ulid()),
                'label' => 'Test Cat',
                'active' => true,
            ],
            ['label' => 'Mutated'],
        );
    }

    public function test_feedback_aggregates_and_snapshots_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            FeedbackSubjectAggregate::class,
            fn (): array => [
                'subject_type' => 'pandit',
                'subject_id' => (string) Str::ulid(),
                'period' => FeedbackSubjectAggregate::PERIOD_LIFETIME,
                'sample_n' => 8,
                'mean_average' => 4.5,
                'bayesian_average' => 4.4,
                'wilson_punctuality_lower' => 0.82,
                'wilson_complaint_free_lower' => 0.85,
                'confidence_tier' => 'ESTABLISHED',
                'prior_code' => 'pandit',
                'prior_version' => 'v1',
                'decay_lambda' => 0.00385,
                'computed_at' => now(),
            ],
            ['sample_n' => 9],
        );

        $this->assertTenantIsolationMatrix(
            FeedbackScoreSnapshot::class,
            fn (): array => [
                'subject_type' => 'pandit',
                'subject_id' => (string) Str::ulid(),
                'period' => 'LIFETIME',
                'sample_n' => 5,
                'bayesian_average' => 4.2,
                'confidence_tier' => 'ESTABLISHED',
                'prior_version' => 'v1',
                'snapshot_at' => now(),
            ],
            ['confidence_tier' => 'HIGH_CONFIDENCE'],
        );
    }

    public function test_support_governance_grievance_and_safety_models_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            SupportGrievance::class,
            fn (): array => [
                'case_id' => (string) Str::ulid(),
                'formal_flag' => true,
                'complaint_record_id' => 'NV-GRV-ISO-'.substr((string) Str::ulid(), -6),
                'verbatim_complaint' => 'Verbatim complaint copy',
                'statutory_ack_due_at' => now()->addHours(48),
                'statutory_redress_due_at' => now()->addMonth(),
                'policy_version' => 'v1',
            ],
            ['officer_decision' => 'Resolved formally'],
        );

        $this->assertTenantIsolationMatrix(
            SupportExternalReferral::class,
            fn (): array => [
                'case_id' => (string) Str::ulid(),
                'channel' => 'NCH',
                'external_reference' => 'NCH-REF-'.substr((string) Str::ulid(), -6),
                'adapter_mode' => 'MANUAL_ENTRY',
                'referred_at' => now(),
            ],
            ['external_status' => 'SUBMITTED'],
        );

        $this->assertTenantIsolationMatrix(
            SupportRefundRequest::class,
            fn (): array => [
                'case_id' => (string) Str::ulid(),
                'booking_id' => (string) Str::ulid(),
                'amount_minor' => 25000,
                'currency' => 'INR',
                'basis' => 'Confirmed cancellation',
                'proposed_type' => 'FULL',
                'remedy_instrument' => RemedyType::MONETARY_ORIGINAL_METHOD->value,
                'requested_by_id' => '1',
                'state' => SupportRefundRequest::STATE_REQUESTED,
            ],
            ['state' => SupportRefundRequest::STATE_EXECUTED],
        );

        $this->assertTenantIsolationMatrix(
            SupportCaseAppeal::class,
            fn (): array => [
                'case_id' => (string) Str::ulid(),
                'appellant_id' => '1',
                'appellant_role' => 'devotee',
                'grounds' => 'Procedural fairness review',
                'status' => SupportCaseAppeal::STATUS_PENDING,
            ],
            ['status' => SupportCaseAppeal::STATUS_UPHELD],
        );

        $this->assertTenantIsolationMatrix(
            SupportCaseEvidence::class,
            fn (): array => [
                'case_id' => (string) Str::ulid(),
                'evidence_reference' => 'NV-EVD-'.substr((string) Str::ulid(), -6),
                'description' => 'Restricted evidence artefact',
                'purpose' => 'investigation',
                'provenance' => 'ORIGINAL_ARTEFACT',
                'storage_reference' => 'safety/vault/test.bin',
                'uploaded_by_id' => '1',
                'visibility_scope' => 'RESTRICTED_SAFETY',
            ],
            ['description' => 'Mutated'],
        );

        $this->assertTenantIsolationMatrix(
            ProviderImprovementPlan::class,
            fn (): array => [
                'subject_type' => 'pandit',
                'subject_id' => (string) Str::ulid(),
                'trigger_pattern' => 'Punctuality threshold',
                'evidence_incident_refs' => ['NV-INC-1'],
                'actions' => ['Dispatch 15m early'],
                'evaluation_window_end' => now()->addDays(30),
                'state' => ProviderImprovementPlan::STATE_PROPOSED,
            ],
            ['state' => ProviderImprovementPlan::STATE_ACKNOWLEDGED],
        );
    }

    public function test_incident_domain_models_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            Incident::class,
            fn (): array => [
                'incident_reference' => 'NV-INC-ISO-'.substr((string) Str::ulid(), -6),
                'type' => 'STREAMING_DISRUPTION',
                'severity' => 'SEV_2',
                'status' => 'DETECTED',
                'attributed_cause' => 'PLATFORM',
                'title' => 'Stream disruption',
                'detected_at' => now(),
            ],
            ['title' => 'Mutated title'],
        );

        $this->assertTenantIsolationMatrix(
            IncidentImpact::class,
            fn (): array => [
                'incident_id' => (string) Str::ulid(),
                'booking_id' => (string) Str::ulid(),
            ],
            ['notes' => 'Mitigated'],
        );

        $this->assertTenantIsolationMatrix(
            IncidentRole::class,
            fn (): array => [
                'incident_id' => (string) Str::ulid(),
                'role' => 'INCIDENT_COMMANDER',
                'actor_id' => '1',
                'assigned_by_id' => '1',
                'assigned_at' => now(),
            ],
            ['role' => 'COMMS_LEAD'],
        );

        $this->assertTenantIsolationMatrix(
            IncidentCorrelation::class,
            fn (): array => [
                'incident_id' => (string) Str::ulid(),
                'correlation_key' => 'corr_'.substr((string) Str::ulid(), -8),
                'score' => 0.85,
                'evidence_json' => ['shared_pandit' => true],
                'status' => IncidentCorrelation::STATUS_PROPOSED,
            ],
            ['status' => IncidentCorrelation::STATUS_CONFIRMED],
        );

        $this->assertTenantIsolationMatrix(
            IncidentReplacement::class,
            fn (): array => [
                'incident_id' => (string) Str::ulid(),
                'booking_id' => (string) Str::ulid(),
                'eligibility_gates_passed' => ['tradition' => true],
                'status' => 'PROPOSED',
            ],
            ['status' => 'CONFIRMED'],
        );

        $this->assertTenantIsolationMatrix(
            IncidentPostmortem::class,
            fn (): array => [
                'incident_id' => (string) Str::ulid(),
                'summary' => 'RTMP relay failover summary',
                'root_cause_notes' => 'Relay timeout',
                'authored_by_id' => '1',
                'completed_at' => now(),
            ],
            ['summary' => 'Updated summary'],
        );

        $this->assertTenantIsolationMatrix(
            IncidentPreventionTask::class,
            fn (): array => [
                'incident_id' => (string) Str::ulid(),
                'title' => 'Automate probe',
                'task_type' => 'MONITORING',
                'owner_id' => '1',
                'status' => 'OPEN',
            ],
            ['status' => 'DONE'],
        );

        $this->assertTenantIsolationMatrix(
            IncidentInterimRestriction::class,
            fn (): array => [
                'subject_type' => 'pandit',
                'subject_id' => (string) Str::ulid(),
                'restriction_kind' => 'HOLD_NEW_BOOKINGS',
                'reason' => 'Pending investigation',
                'proportionality_note' => 'Bounded 14 days',
                'requested_by_id' => '1',
                'approved_by_safety_lead_id' => '2',
                'expires_at' => now()->addDays(14),
            ],
            ['lifted_by_id' => '3'],
        );
    }
}

