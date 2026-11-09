<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $conn = 'tenant_shared';

        // 1. Tax Registrations
        Schema::connection($conn)->create('tax_registrations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('jurisdiction', 8); // IN, US, GB, AE
            $table->string('tax_type', 32); // GST, SALES_TAX, VAT
            $table->string('registration_number', 64); // GSTIN, Tax ID, VAT No
            $table->string('state_province', 64)->nullable(); // e.g. MH, DL, CA, NY
            $table->boolean('is_headquarters')->default(false);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->string('status', 24)->default('ACTIVE'); // ACTIVE, SUSPENDED, EXPIRED
            $table->timestamps();

            $table->index(['tenant_id', 'jurisdiction', 'status'], 'idx_tax_reg_tenant_jur');
        });

        // 2. Tax Codes
        Schema::connection($conn)->create('tax_codes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 64);
            $table->string('name', 128);
            $table->string('category', 32)->default('SERVICE'); // SERVICE, GOODS
            $table->string('jurisdiction', 8)->default('ALL'); // IN, US, GB, AE, ALL
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code', 'jurisdiction'], 'uq_tax_codes_code_jur');
        });

        // 3. Tax Rule Versions
        Schema::connection($conn)->create('tax_rule_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('jurisdiction', 8); // IN, US, GB, AE
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->json('rules_json');
            $table->timestamps();

            $table->unique(['jurisdiction', 'version'], 'uq_tax_rules_jur_ver');
            $table->index(['jurisdiction', 'effective_from', 'effective_to'], 'idx_tax_rules_effective');
        });

        // 4. Tax Computations
        Schema::connection($conn)->create('tax_computations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('quote_id', 64)->nullable();
            $table->string('booking_id', 64)->nullable();
            $table->string('order_id', 64)->nullable();
            $table->string('jurisdiction', 8); // IN, US, GB, AE
            $table->string('currency', 3);
            $table->bigInteger('taxable_amount_minor');
            $table->bigInteger('tax_amount_minor');
            $table->bigInteger('total_amount_minor');
            $table->json('components_json');
            $table->string('status', 32)->default('QUOTED'); // QUOTED, COMMITTED, CANCELLED
            $table->timestamps();

            $table->index(['tenant_id', 'booking_id'], 'idx_tax_comp_booking');
            $table->index(['tenant_id', 'quote_id'], 'idx_tax_comp_quote');
            $table->index(['tenant_id', 'status'], 'idx_tax_comp_status');
        });
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        Schema::connection($conn)->dropIfExists('tax_computations');
        Schema::connection($conn)->dropIfExists('tax_rule_versions');
        Schema::connection($conn)->dropIfExists('tax_codes');
        Schema::connection($conn)->dropIfExists('tax_registrations');
    }
};
