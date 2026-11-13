<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Financial Invoices and Immutable Snapshots (E-CM-07 / Task T-CM07-02).
 * Stores GST Rule 46 particulars, immutable pricing snapshots, and atomic FY serials.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('financial_invoices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('invoice_number', 32)->unique();
            $table->string('booking_id', 32)->index();
            $table->unsignedBigInteger('user_id');
            $table->string('fy_year', 8); // e.g. 2026-27
            $table->unsignedInteger('sequence_number');
            $table->string('supplier_model', 32); // facilitator | direct_temple | direct_pandit
            $table->string('supplier_gstin', 15);
            $table->string('recipient_gstin', 15)->nullable();
            $table->string('place_of_supply', 64);
            $table->string('sac_code', 16)->default('999799');
            $table->unsignedInteger('taxable_amount_cents');
            $table->unsignedInteger('cgst_cents')->default(0);
            $table->unsignedInteger('sgst_cents')->default(0);
            $table->unsignedInteger('igst_cents')->default(0);
            $table->unsignedInteger('total_amount_cents');
            $table->json('snapshot_json');
            $table->string('status', 16)->default('issued');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'fy_year', 'sequence_number'], 'comm_invoices_tenant_fy_seq_uq');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('financial_invoices');
    }
};

