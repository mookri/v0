<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor orders (P12): per-vendor sales with a linear lifecycle
 * (NEW → PROCESSING → DISPATCHED → DELIVERED, cancellable before
 * dispatch). reference is unique per vendor; money stays minor-unit
 * integers. Items and transitions live in their own tables.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('vendor_orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('vendor_id');
            $table->string('reference', 64);
            $table->string('customer_name', 255)->nullable();
            $table->string('status', 24)->default('NEW'); // NEW | PROCESSING | DISPATCHED | DELIVERED | CANCELLED
            $table->string('currency', 3)->default('INR');
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->unique(['tenant_id', 'vendor_id', 'reference']);
            $table->index(['tenant_id', 'vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->drop('vendor_orders');
    }
};
