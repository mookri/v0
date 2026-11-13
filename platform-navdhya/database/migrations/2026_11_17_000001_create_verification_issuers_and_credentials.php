<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('verification_issuers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name', 180);
            $table->string('name_devanagari', 180)->nullable();
            $table->string('kind', 32); // pathashala|akhara|university|board|individual_guru|other
            $table->string('jurisdiction_state', 64)->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->string('registry_reference', 120)->nullable();
            $table->string('contact_channel', 180)->nullable();
            $table->timestamp('contact_verified_at')->nullable();
            $table->decimal('response_rate', 5, 2)->default(0);
            $table->timestamp('last_confirmed_at')->nullable();
            $table->string('evidence_grade', 32)->default('unconfirmed')->index();
            $table->text('equivalence_notes')->nullable();
            $table->string('policy_version', 64)->default('policy-v1');
            $table->timestamps();
        });

        Schema::connection('tenant_shared')->create('verification_credentials', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('identity_id', 64);
            $table->string('subject_type', 32)->default('pandit');
            $table->string('subject_id', 64);
            $table->string('application_id', 26)->nullable();
            $table->string('issuer_id', 26);
            $table->string('credential_type', 48);
            $table->string('certificate_ref_hmac', 64);
            $table->string('certificate_ref_masked', 32)->nullable();
            $table->unsignedSmallInteger('award_year')->nullable();
            $table->string('programme_ref', 120)->nullable();
            $table->json('subject_scope_json')->nullable();
            $table->string('mode_attained', 24)->default('manual');
            $table->string('official_response_ref', 120)->nullable();
            $table->string('claim_id', 26)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 180)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'identity_id'], 'vcred_tenant_identity_idx');
            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'vcred_tenant_subject_idx');
            $table->index(['issuer_id', 'certificate_ref_hmac'], 'vcred_issuer_cert_hmac_idx');
        });

        Schema::connection('tenant_shared')->create('verification_credential_responses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('credential_id', 26);
            $table->string('issuer_id', 26);
            $table->string('outreach_stage', 32); // REQUEST_SENT|ACKNOWLEDGED|RESPONSE_RECEIVED|NO_RESPONSE|DENIED
            $table->string('responder_name', 120)->nullable();
            $table->string('responder_role', 120)->nullable();
            $table->text('response_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'credential_id'], 'vcresp_tenant_cred_idx');
        });

        Schema::connection('tenant_shared')->create('sampradaya_attestations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_type', 32)->default('pandit');
            $table->string('subject_id', 64);
            $table->string('sampradaya', 80)->nullable();
            $table->string('assessor_identity_id', 64);
            $table->string('assessor_credential_id', 26)->nullable();
            $table->string('ritual_category', 64);
            $table->json('scope_tags_json');
            $table->json('rubric_scores_json');
            $table->unsignedSmallInteger('overall_score')->default(0);
            $table->boolean('passed')->default(false);
            $table->string('claim_id', 26)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'samp_att_tenant_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('sampradaya_attestations');
        Schema::connection('tenant_shared')->dropIfExists('verification_credential_responses');
        Schema::connection('tenant_shared')->dropIfExists('verification_credentials');
        Schema::connection('central')->dropIfExists('verification_issuers');
    }
};

