<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add pandit_id to catalog_prices for full matrix pricing (SKU × pandit × market × currency).
 * Nullable — when null, price applies to all pandits (base price).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('catalog_prices', function (Blueprint $table): void {
            $table->unsignedBigInteger('pandit_id')->nullable()->after('currency');
            $table->index(['tenant_id', 'sku', 'market', 'pandit_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('catalog_prices', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'sku', 'market', 'pandit_id']);
            $table->dropColumn('pandit_id');
        });
    }
};
