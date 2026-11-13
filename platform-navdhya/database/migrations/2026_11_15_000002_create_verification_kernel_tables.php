<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('verification_policy_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('version', 64)->unique();
            $table->string('track', 32)->index();
            $table->json('rules_json');
            $table->timestamp('active_from');
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant_shared')->create('verification_claims', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('application_id', 26)->nullable();
            $table->string('claim_kind', 48);
            $table->string('mode', 24)->default('manual');
            $table->string('confidence', 24)->default('unassessed');
            $table->string('status', 24)->default('pending');
            $table->string('policy_version', 64)->default('policy-v1');
            $table->boolean('legacy_asserted')->default(false);
            $table->string('reason_code', 64)->nullable();
            $table->text('note')->nullable();
            $table->string('decided_by', 64)->nullable();
            $table->string('countersigned_by', 64)->nullable();
            $table->string('evidence_set_hash', 64)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_by', 64)->nullable();
            $table->string('revocation_reason', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id', 'status'], 'vc_tenant_subject_status_idx');
            $table->index(['tenant_id', 'status', 'expires_at'], 'vc_tenant_status_exp_idx');
            $table->index(['tenant_id', 'application_id'], 'vc_tenant_app_idx');
        });

        Schema::connection('tenant_shared')->create('verification_claim_scopes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('claim_id', 26);
            $table->string('scope_type', 32);
            $table->string('scope_value', 128);
            $table->timestamps();

            $table->index(['tenant_id', 'claim_id'], 'vcs_tenant_claim_idx');
            $table->unique(['tenant_id', 'claim_id', 'scope_type', 'scope_value'], 'vcs_unique_scope');
        });

        Schema::connection('tenant_shared')->create('verification_evidence', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('claim_id', 26);
            $table->string('document_id', 26)->nullable();
            $table->string('evidence_kind', 48);
            $table->string('source_mode', 24)->default('manual');
            $table->string('source_ref', 191)->nullable();
            $table->string('sha256', 64)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'claim_id'], 've_tenant_claim_idx');
            $table->index(['tenant_id', 'document_id'], 've_tenant_doc_idx');
        });

        Schema::connection('tenant_shared')->create('onboarding_decisions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('application_id', 26);
            $table->string('decision', 24);
            $table->string('status', 24)->default('pending_countersign');
            $table->string('reason_code', 64)->nullable();
            $table->text('note')->nullable();
            $table->string('primary_reviewer_id', 64);
            $table->string('second_reviewer_id', 64)->nullable();
            $table->string('evidence_set_hash', 64);
            $table->string('policy_version', 64)->default('policy-v1');
            $table->timestamp('countersigned_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'application_id', 'status'], 'od_tenant_app_status_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('onboarding_decisions');
        Schema::connection('tenant_shared')->dropIfExists('verification_evidence');
        Schema::connection('tenant_shared')->dropIfExists('verification_claim_scopes');
        Schema::connection('tenant_shared')->dropIfExists('verification_claims');
        Schema::connection('central')->dropIfExists('verification_policy_versions');
    }
};
