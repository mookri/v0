<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('verification_jurisdictions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('state_code', 16);
            $table->string('title', 180);
            $table->json('checklist_json');
            $table->boolean('manual_mode_only')->default(true);
            $table->timestamps();
        });

        Schema::connection('tenant_shared')->create('verification_entity_relationships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('source_type', 32);
            $table->string('source_id', 64);
            $table->string('edge_type', 48); // governed_by|has_governing_body|member_of|has_authority_grant|owns_payout_instrument
            $table->string('target_type', 32);
            $table->string('target_id', 64);
            $table->string('claim_id', 26)->nullable();
            $table->string('evidence_id', 64)->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'source_type', 'source_id', 'edge_type'], 'ver_rel_source_idx');
            $table->index(['tenant_id', 'target_type', 'target_id'], 'ver_rel_target_idx');
        });

        Schema::connection('tenant_shared')->create('verification_authority_grants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('entity_id', 64);
            $table->string('person_identity_id', 64);
            $table->string('role_title', 80);
            $table->string('authority_scope', 40); // onboarding|profile_management|bank_mandate|full_representation
            $table->string('legal_form', 48)->nullable();
            $table->string('jurisdiction_code', 64)->nullable();
            $table->string('registration_fingerprint_hmac', 64)->nullable();
            $table->string('resolution_ref', 120)->nullable();
            $table->date('resolution_date')->nullable();
            $table->string('co_sign_trustee_id', 64)->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('claim_id', 26)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'entity_id', 'revoked_at'], 'vag_tenant_entity_idx');
            $table->index(['tenant_id', 'registration_fingerprint_hmac'], 'vag_tenant_reg_hmac_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('verification_authority_grants');
        Schema::connection('tenant_shared')->dropIfExists('verification_entity_relationships');
        Schema::connection('central')->dropIfExists('verification_jurisdictions');
    }
};

