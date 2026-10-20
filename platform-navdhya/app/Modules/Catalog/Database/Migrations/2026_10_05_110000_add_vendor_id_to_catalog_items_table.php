<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add vendor_id to catalog_items (Vendor module): nullable soft reference
 * to vendors.id (ULID) — same pattern as catalog_prices.pandit_id (indexed
 * column, no FK) so the catalog migration stays independent of the Vendor
 * module's table loading order. null = tenant-owned item (not vendor-sold).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('catalog_items', function (Blueprint $table): void {
            $table->ulid('vendor_id')->nullable()->after('tenant_id');
            $table->index(['tenant_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('catalog_items', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }
};
