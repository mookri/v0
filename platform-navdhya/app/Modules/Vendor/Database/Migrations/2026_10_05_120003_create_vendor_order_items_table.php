<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor order items (P12): immutable line snapshots (name/unit captured
 * at placement so later catalog edits never rewrite history).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('vendor_order_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('order_id');
            $table->string('sku', 64);
            $table->string('name', 255);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_minor');
            $table->unsignedBigInteger('line_minor');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('vendor_orders')->onDelete('cascade');
            $table->index(['tenant_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->drop('vendor_order_items');
    }
};
