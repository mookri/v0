<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('tenant_shared');

        $schema->create('incidents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_reference', 32);
            $table->string('type', 48);
            $table->string('severity', 16);
            $table->string('status', 32);
            $table->string('attributed_cause', 32)->default('UNKNOWN');
            $table->string('title', 255);
            $table->text('summary')->nullable();
            $table->string('commander_id', 64)->nullable();
            $table->string('safety_lead_id', 64)->nullable();
            $table->boolean('safety_restricted')->default(false);
            $table->string('correlation_id', 64)->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('contained_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('postmortem_completed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'incident_reference'], 'inc_tenant_ref_uq');
            $table->index(['tenant_id', 'status', 'severity'], 'inc_status_sev_idx');
            $table->index(['tenant_id', 'safety_restricted'], 'inc_safety_idx');
        });

        $schema->create('incident_impacts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('booking_id', 26);
            $table->string('subject_type', 32)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('attributed_cause', 32)->default('UNKNOWN');
            $table->string('devotee_outcome', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'incident_id', 'booking_id'], 'inc_imp_uq');
            $table->index(['tenant_id', 'booking_id'], 'inc_imp_booking_idx');
        });

        $schema->create('incident_roles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('role', 40);
            $table->string('actor_id', 64);
            $table->string('assigned_by_id', 64);
            $table->timestamp('assigned_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'incident_id', 'role'], 'inc_roles_idx');
        });

        $schema->create('incident_timeline', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('entry_type', 48);
            $table->string('actor_id', 64);
            $table->text('summary');
            $table->json('metadata_json')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'incident_id', 'occurred_at'], 'inc_tl_idx');
        });

        $schema->create('incident_actions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('action_type', 48);
            $table->string('actor_id', 64);
            $table->text('notes');
            $table->timestamp('executed_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'incident_id'], 'inc_act_idx');
        });

        $schema->create('incident_correlations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('candidate_booking_id', 26)->nullable();
            $table->string('candidate_incident_id', 26)->nullable();
            $table->string('correlation_key', 64);
            $table->decimal('score', 4, 2);
            $table->json('evidence_json');
            $table->string('status', 24)->default('PROPOSED');
            $table->string('confirmed_by_id', 64)->nullable();
            $table->string('revoked_by_id', 64)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'incident_id', 'status'], 'inc_corr_idx');
        });

        $schema->create('incident_replacements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('booking_id', 26);
            $table->string('original_pandit_id', 64)->nullable();
            $table->string('candidate_pandit_id', 64)->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->json('eligibility_gates_passed');
            $table->string('gate_disclosure_status', 32)->default('FULL_GATES');
            $table->string('status', 32)->default('PROPOSED');
            $table->timestamp('offer_expires_at')->nullable();
            $table->timestamp('devotee_consented_at')->nullable();
            $table->string('confirmed_by_id', 64)->nullable();
            $table->string('fallback_action', 40)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'incident_id', 'booking_id'], 'inc_rep_idx');
            $table->index(['tenant_id', 'candidate_pandit_id', 'status'], 'inc_rep_cand_idx');
        });

        $schema->create('incident_communications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('audience', 32);
            $table->string('channel', 32);
            $table->string('recipient_ref', 128);
            $table->text('message_summary');
            $table->string('approved_by_id', 64);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['tenant_id', 'incident_id'], 'inc_comm_idx');
        });

        $schema->create('incident_postmortems', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26)->unique();
            $table->text('summary');
            $table->text('root_cause_notes');
            $table->json('contributing_factors')->nullable();
            $table->string('authored_by_id', 64);
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'incident_id'], 'inc_pm_idx');
        });

        $schema->create('incident_prevention_tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('postmortem_id', 26)->nullable();
            $table->string('title', 190);
            $table->string('task_type', 40);
            $table->string('owner_id', 64);
            $table->timestamp('due_at')->nullable();
            $table->string('status', 24)->default('OPEN');
            $table->string('linked_case_id', 26)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'incident_id', 'status'], 'inc_prev_idx');
        });

        $schema->create('incident_severity_history', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26);
            $table->string('previous_severity', 16);
            $table->string('new_severity', 16);
            $table->string('actor_id', 64);
            $table->text('reason');
            $table->boolean('is_downgrade')->default(false);
            $table->timestamp('occurred_at');

            $table->index(['tenant_id', 'incident_id', 'occurred_at'], 'inc_sev_hist_idx');
        });

        $schema->create('incident_interim_restrictions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('incident_id', 26)->nullable();
            $table->string('support_case_id', 26)->nullable();
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('restriction_kind', 32);
            $table->text('reason');
            $table->text('proportionality_note');
            $table->string('requested_by_id', 64);
            $table->string('approved_by_safety_lead_id', 64);
            $table->timestamp('expires_at');
            $table->timestamp('lifted_at')->nullable();
            $table->string('lifted_by_id', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id', 'lifted_at'], 'inc_restr_subj_idx');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant_shared');
        $schema->dropIfExists('incident_interim_restrictions');
        $schema->dropIfExists('incident_severity_history');
        $schema->dropIfExists('incident_prevention_tasks');
        $schema->dropIfExists('incident_postmortems');
        $schema->dropIfExists('incident_communications');
        $schema->dropIfExists('incident_replacements');
        $schema->dropIfExists('incident_correlations');
        $schema->dropIfExists('incident_actions');
        $schema->dropIfExists('incident_timeline');
        $schema->dropIfExists('incident_roles');
        $schema->dropIfExists('incident_impacts');
        $schema->dropIfExists('incidents');
    }
};

