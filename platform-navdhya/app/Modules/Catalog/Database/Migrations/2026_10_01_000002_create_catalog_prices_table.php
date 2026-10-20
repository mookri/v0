<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog prices (Wave 3): minor-unit integers, ISO currency, effective
 * dating. One active price per (sku, market, currency) at any instant;
 * history retained (no updates — supersede by ending effective_to).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('catalog_prices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('sku', 64);
            $table->string('market', 16)->default('in');
            $table->string('currency', 3);
            $table->unsignedBigInteger('price_minor');
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sku', 'market']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('catalog_prices');
    }
};
