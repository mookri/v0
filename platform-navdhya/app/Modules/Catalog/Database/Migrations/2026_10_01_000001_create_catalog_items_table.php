<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog items (Wave 3): tenant-scoped sellables with SKU uniqueness per
 * tenant (TEN-010), monetary minor units as integers, status lifecycle.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('catalog_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('sku', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 24)->default('DRAFT');
            $table->string('currency', 3)->default('INR');
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('catalog_items');
    }
};
