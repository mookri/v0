<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * catalog_prices.pandit_id becomes a ULID string (BACKEND-RCA
 * 2026-10-21, finding W6).
 *
 * The column was an unsigned bigint while every pandit id is a 26
 * character ULID, so a pandit-specific price could never be stored or
 * matched: the quote always fell back to the base price, and a
 * `pandit_id` of 1 would have addressed a pandit that does not exist.
 */
return new class () extends Migration {
    public function up(): void
    {
        // The column is part of the pricing index, so the index is
        // dropped before the type change and restored after it.
        Schema::connection('tenant_shared')->table('catalog_prices', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'sku', 'market', 'pandit_id']);
        });

        Schema::connection('tenant_shared')->table('catalog_prices', function (Blueprint $table): void {
            $table->string('pandit_id', 26)->nullable()->change();
        });

        Schema::connection('tenant_shared')->table('catalog_prices', function (Blueprint $table): void {
            $table->index(['tenant_id', 'sku', 'market', 'pandit_id']);
        });
    }

    public function down(): void
    {
        // A ULID cannot be represented as an integer again, so the
        // pandit-specific rows are reset to the base price first.
        DB::connection('tenant_shared')->table('catalog_prices')
            ->whereNotNull('pandit_id')
            ->update(['pandit_id' => null]);

        Schema::connection('tenant_shared')->table('catalog_prices', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'sku', 'market', 'pandit_id']);
        });

        Schema::connection('tenant_shared')->table('catalog_prices', function (Blueprint $table): void {
            $table->unsignedBigInteger('pandit_id')->nullable()->change();
        });

        Schema::connection('tenant_shared')->table('catalog_prices', function (Blueprint $table): void {
            $table->index(['tenant_id', 'sku', 'market', 'pandit_id']);
        });
    }
};
