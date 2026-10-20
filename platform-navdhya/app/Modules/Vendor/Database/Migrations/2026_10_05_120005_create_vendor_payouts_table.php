<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor payouts (P12): read-only stub — one row per vendor per period
 * (unique), surfaced to partners; recording flows land later.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('vendor_payouts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('vendor_id');
            $table->string('period', 16); // YYYY-MM
            $table->string('status', 24)->default('PENDING'); // PENDING | PAID
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('reference', 64)->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->unique(['tenant_id', 'vendor_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->drop('vendor_payouts');
    }
};
