<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full Support, Grievance, Safety Evidence, Resolution, Appeal & Refund Request
 * schema on `tenant_shared` (TS-03, TS-04, TS-10, TS-11, TS-14, TS-15, §16).
 *
 * Notice all status/category columns use varchar(40)/varchar(64) (verified
 * via TrustStateMachine::assertColumnWidthFits against TS-03 truncation).
 */
return new class () extends Migration {
    public function up(): void
    {
        $schema = Schema::connection('tenant_shared');

        $schema->create('support_cases', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_reference', 32);
            $table->unsignedBigInteger('reporter_user_id');
            $table->string('subject', 190);
            $table->string('case_type', 40);
            $table->string('category', 64);
            $table->string('status', 40)->default('NEW');
            $table->string('urgency', 24)->default('NORMAL');
            $table->string('visibility_scope', 32)->default('PARTICIPANT');
            $table->string('subject_type', 32)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('booking_id', 26)->nullable();
            $table->string('payment_id', 64)->nullable();
            $table->string('incident_id', 26)->nullable();
            $table->string('review_id', 26)->nullable();
            $table->string('legacy_ticket_id', 26)->nullable();
            $table->string('intake_channel', 32)->default('web');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('sla_policy_version', 32)->nullable();
            $table->string('assignee_id', 64)->nullable();
            $table->string('assigned_team', 64)->nullable();
            $table->unsignedInteger('reopen_count')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'case_reference']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'visibility_scope', 'status']);
            $table->index(['tenant_id', 'reporter_user_id']);
            $table->index(['tenant_id', 'booking_id']);
            $table->index(['tenant_id', 'incident_id']);
        });

        $schema->create('support_case_participants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('actor_id', 64);
            $table->string('party_role', 32);
            $table->string('entitlement_scope', 32);
            $table->timestamps();

            $table->index(['tenant_id', 'case_id']);
        });

        $schema->create('support_case_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('author_id', 64);
            $table->string('author_role', 32);
            $table->string('visibility_scope', 32)->default('PARTICIPANT');
            $table->text('body');
            $table->timestamps();

            $table->index(['tenant_id', 'case_id']);
        });

        $schema->create('support_case_evidence', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('evidence_reference', 32);
            $table->string('description', 255);
            $table->string('purpose', 64);
            $table->string('provenance', 32); // ORIGINAL_ARTEFACT | DERIVED_INTERPRETATION | EXTERNAL_RECORD
            $table->string('storage_reference', 255);
            $table->string('sensitivity_class', 32)->default('STANDARD');
            $table->string('retention_class', 32)->default('STANDARD_CASE');
            $table->string('uploaded_by_id', 64);
            $table->timestamp('retained_until')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'evidence_reference']);
            $table->index(['tenant_id', 'case_id']);
        });

        $schema->create('support_case_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('assignee_id', 64);
            $table->string('assigned_by_id', 64);
            $table->string('team', 64)->nullable();
            $table->string('reason', 255);
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->index(['tenant_id', 'case_id']);
        });

        $schema->create('support_case_status_history', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('actor_id', 64);
            $table->string('actor_type', 32);
            $table->string('action', 64);
            $table->string('previous_state', 40);
            $table->string('new_state', 40);
            $table->string('reason', 500);
            $table->string('policy_version', 32)->default('v1');
            $table->string('correlation_id', 64);
            $table->timestamp('occurred_at');

            $table->index(['tenant_id', 'case_id', 'occurred_at']);
        });

        $schema->create('support_case_resolutions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('remedy_type', 40);
            $table->text('decision_summary');
            $table->string('decided_by_id', 64);
            $table->string('proposed_by_id', 64);
            $table->string('accepted_by_id', 64)->nullable();
            $table->timestamp('consent_captured_at')->nullable();
            $table->json('dossier_json')->nullable();
            $table->string('policy_version', 32)->default('v1');
            $table->timestamps();

            $table->index(['tenant_id', 'case_id']);
        });

        $schema->create('support_case_appeals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('appellant_id', 64);
            $table->string('appellant_role', 32);
            $table->text('grounds');
            $table->string('status', 32)->default('PENDING');
            $table->string('outcome', 32)->nullable();
            $table->string('reviewer_id', 64)->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'case_id']);
        });

        $schema->create('support_refund_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('booking_id', 26);
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 8)->default('INR');
            $table->string('basis', 190);
            $table->string('proposed_type', 24); // FULL | PARTIAL
            $table->string('remedy_instrument', 40);
            $table->timestamp('devotee_freely_consented_at')->nullable();
            $table->string('requested_by_id', 64);
            $table->string('approved_by_id', 64)->nullable();
            $table->string('finance_execution_reference', 64)->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->string('state', 32)->default('REQUESTED');
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'case_id']);
            $table->index(['tenant_id', 'state']);
        });

        $schema->create('support_grievances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26)->unique();
            $table->boolean('formal_flag')->default(true);
            $table->string('complaint_record_id', 32);
            $table->text('verbatim_complaint');
            $table->string('complaint_copy_storage_ref', 255)->nullable();
            $table->timestamp('copy_issued_at')->nullable();
            $table->timestamp('statutory_ack_due_at');
            $table->timestamp('statutory_redress_due_at');
            $table->string('officer_id', 64)->nullable();
            $table->text('officer_decision')->nullable();
            $table->string('policy_version', 32);
            $table->timestamp('breached_ack_at')->nullable();
            $table->timestamp('breached_redress_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'statutory_ack_due_at']);
            $table->index(['tenant_id', 'statutory_redress_due_at']);
        });

        $schema->create('support_external_referrals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('case_id', 26);
            $table->string('channel', 32); // NCH | OTHER
            $table->string('external_reference', 120);
            $table->string('adapter_mode', 32); // manual_fallback | partner_api
            $table->timestamp('referred_at');
            $table->timestamp('last_reconciled_at')->nullable();
            $table->string('external_status', 64)->default('SUBMITTED');
            $table->json('payload_snapshot')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'case_id']);
        });

        $schema->create('trust_sla_policies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('scope', 24); // internal | statutory
            $table->string('metric', 40); // acknowledge | redress | first_response | safety_triage
            $table->unsignedInteger('duration_minutes');
            $table->string('calendar_handling', 32); // calendar_hours | business_hours | calendar_month
            $table->timestamp('effective_from');
            $table->timestamp('effective_until')->nullable();
            $table->string('approved_by_officer_id', 64)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_status', 32)->default('PENDING_COUNSEL_APPROVAL');
            $table->string('version', 32);
            $table->timestamps();

            $table->index(['tenant_id', 'scope', 'metric']);
        });

        $schema->create('trust_taxonomies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('kind', 32); // category | case_type | severity
            $table->string('code', 64);
            $table->string('label', 120);
            $table->boolean('active')->default(true);
            $table->string('policy_version', 32)->default('v1');
            $table->timestamps();

            $table->unique(['tenant_id', 'kind', 'code']);
        });

        $schema->create('provider_improvement_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('trigger_pattern', 120);
            $table->unsignedInteger('excluded_external_causes_count')->default(0);
            $table->json('evidence_incident_refs');
            $table->json('actions');
            $table->timestamp('provider_acknowledged_at')->nullable();
            $table->text('provider_notes')->nullable();
            $table->timestamp('evaluation_window_end');
            $table->json('outcome_metrics')->nullable();
            $table->string('state', 32)->default('PROPOSED');
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant_shared');
        $schema->dropIfExists('provider_improvement_plans');
        $schema->dropIfExists('trust_taxonomies');
        $schema->dropIfExists('trust_sla_policies');
        $schema->dropIfExists('support_external_referrals');
        $schema->dropIfExists('support_grievances');
        $schema->dropIfExists('support_refund_requests');
        $schema->dropIfExists('support_case_appeals');
        $schema->dropIfExists('support_case_resolutions');
        $schema->dropIfExists('support_case_status_history');
        $schema->dropIfExists('support_case_assignments');
        $schema->dropIfExists('support_case_evidence');
        $schema->dropIfExists('support_case_messages');
        $schema->dropIfExists('support_case_participants');
        $schema->dropIfExists('support_cases');
    }
};

