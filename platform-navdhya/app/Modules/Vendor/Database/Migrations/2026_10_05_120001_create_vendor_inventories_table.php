<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor inventory (P12): on-hand stock per vendor SKU. sku mirrors the
 * catalog item SKU the vendor sells (soft link — catalog is another
 * module); rows are created lazily on first positive adjustment.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('vendor_inventories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('vendor_id');
            $table->string('sku', 64);
            $table->unsignedInteger('on_hand')->default(0);
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->unique(['tenant_id', 'vendor_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->drop('vendor_inventories');
    }
};
