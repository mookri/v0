<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('verification_tax_registrations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('application_id', 26)->nullable();
            $table->string('gst_status', 40)->default('registered'); // registered|composition_dealer|exempt_below_threshold|unregistered_individual
            $table->text('gstin_encrypted')->nullable();
            $table->string('gstin_hmac', 64)->nullable();
            $table->string('gstin_masked', 20)->nullable();
            $table->string('state_code', 4)->nullable();
            $table->string('legal_name', 180)->nullable();
            $table->json('trade_names_json')->nullable();
            $table->string('registration_status', 24)->default('active'); // active|cancelled|suspended|not_found|exempt
            $table->string('verifier_mode', 24)->default('native'); // native|manual|official
            $table->string('verification_claim_id', 26)->nullable();
            $table->string('verified_by', 64)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'vtax_tenant_subject_idx');
            $table->index(['tenant_id', 'gstin_hmac'], 'vtax_tenant_gstin_hmac_idx');
        });

        Schema::connection('tenant_shared')->create('verification_bank_instruments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('application_id', 26)->nullable();
            $table->string('account_fingerprint', 64);
            $table->text('account_number_encrypted');
            $table->char('account_last4', 4);
            $table->text('ifsc_encrypted');
            $table->string('ifsc_prefix_masked', 16);
            $table->string('account_holder_name', 180);
            $table->string('account_type', 24)->default('individual'); // individual|joint|entity
            $table->string('ownership_type', 48)->nullable();
            $table->string('verifier_mode', 24)->default('manual'); // manual|financial
            $table->string('verification_status', 24)->default('pending'); // pending|verified|manual_review|hold|rejected
            $table->decimal('name_match_score', 5, 4)->nullable();
            $table->string('provider_reference', 120)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by', 64)->nullable();
            $table->string('supersedes_id', 26)->nullable();
            $table->timestamp('cooling_off_until')->nullable();
            $table->timestamp('discrepancy_frozen_at')->nullable();
            $table->string('discrepancy_reason', 180)->nullable();
            $table->string('claim_id', 26)->nullable();
            $table->text('policy_exception_note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'vbank_tenant_subject_idx');
            $table->index(['tenant_id', 'account_fingerprint'], 'vbank_tenant_fingerprint_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('verification_bank_instruments');
        Schema::connection('tenant_shared')->dropIfExists('verification_tax_registrations');
    }
};

