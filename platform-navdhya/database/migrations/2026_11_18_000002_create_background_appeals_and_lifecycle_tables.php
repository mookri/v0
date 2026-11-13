<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('verification_regulatory_gates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('gate_key', 64)->unique();
            $table->string('classification_status', 48)->default('unclassified_pending_counsel');
            $table->string('counsel_opinion_ref', 120)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->boolean('aml_features_enabled')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant_shared')->create('verification_background_checks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('application_id', 26)->nullable();
            $table->string('check_type', 48); // identity|qualification|experience_references|institutional_association|address|police_antecedents|platform_fraud_indicators|disciplinary_history
            $table->string('lawful_basis', 64);
            $table->string('consent_id', 26)->nullable();
            $table->string('scope_searched', 120)->nullable();
            $table->string('source', 80)->nullable();
            $table->timestamp('searched_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('findings_summary')->nullable();
            $table->string('legal_status', 40)->default('not_available'); // PoliceAntecedentStatus
            $table->string('evidence_ref', 120)->nullable();
            $table->string('reviewer_id', 64)->nullable();
            $table->string('confidence', 24)->default('unassessed');
            $table->boolean('human_relevance_assessed')->default(false);
            $table->boolean('action_required')->default(false);
            $table->text('applicant_clarification')->nullable();
            $table->string('claim_id', 26)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'vbg_tenant_subject_idx');
        });

        Schema::connection('tenant_shared')->create('onboarding_appeals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('application_id', 26);
            $table->string('decision_id', 26)->nullable();
            $table->string('applicant_identity_id', 64);
            $table->string('original_actor_id', 64)->nullable();
            $table->string('original_countersign_actor_id', 64)->nullable();
            $table->text('applicant_statement');
            $table->json('new_evidence_ids_json')->nullable();
            $table->string('grounds_code', 64);
            $table->string('assigned_reviewer_id', 64)->nullable();
            $table->string('outcome', 32)->nullable(); // upheld|overturned|remanded
            $table->text('outcome_note')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'application_id'], 'oap_tenant_app_idx');
            $table->index(['tenant_id', 'outcome'], 'oap_tenant_outcome_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('onboarding_appeals');
        Schema::connection('tenant_shared')->dropIfExists('verification_background_checks');
        Schema::connection('central')->dropIfExists('verification_regulatory_gates');
    }
};

