<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('privacy_retention_policies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('dataset_key', 64)->unique();
            $table->unsignedInteger('retention_days');
            $table->boolean('legal_hold')->default(false);
            $table->string('action', 24)->default('purge');
            $table->timestamps();
        });

        Schema::connection('central')->create('privacy_incidents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->nullable()->index();
            $table->string('severity', 24);
            $table->string('category', 64);
            $table->text('summary');
            $table->unsignedInteger('affected_subjects_count')->default(0);
            $table->timestamp('detected_at');
            $table->timestamp('reported_to_authority_at')->nullable();
            $table->string('reported_by', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('verification_provider_registry', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('provider_code', 64)->unique();
            $table->string('jurisdiction', 32)->default('IN');
            $table->boolean('dpa_executed')->default(false);
            $table->string('dpa_ref', 128)->nullable();
            $table->boolean('subprocessor_approved')->default(false);
            $table->string('residency_region', 48)->default('ap-south-1');
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        Schema::connection('tenant_shared')->create('privacy_consents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_identity_id', 64);
            $table->string('purpose', 48);
            $table->boolean('granted')->default(true);
            $table->string('policy_version', 48);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_identity_id', 'purpose'], 'pc_tenant_subject_purpose_idx');
        });

        Schema::connection('tenant_shared')->create('privacy_subject_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_identity_id', 64);
            $table->string('request_type', 24);
            $table->string('status', 24)->default('received');
            $table->text('reason')->nullable();
            $table->json('correction_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('handled_by', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_identity_id', 'request_type'], 'psr_tenant_subject_type_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('privacy_subject_requests');
        Schema::connection('tenant_shared')->dropIfExists('privacy_consents');
        Schema::connection('central')->dropIfExists('verification_provider_registry');
        Schema::connection('central')->dropIfExists('privacy_incidents');
        Schema::connection('central')->dropIfExists('privacy_retention_policies');
    }
};

