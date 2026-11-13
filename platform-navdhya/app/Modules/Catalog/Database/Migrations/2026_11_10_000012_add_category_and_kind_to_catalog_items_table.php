<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('catalog_items', function (Blueprint $table) {
            $table->string('category_id', 26)->nullable()->after('tenant_id');
            $table->string('slug', 100)->nullable()->after('name');
            $table->string('item_kind', 30)->default('SERVICE')->after('status'); // SERVICE | GOODS | KIT
            $table->unsignedSmallInteger('base_duration_minutes')->nullable()->after('price_minor');

            $table->index(['tenant_id', 'category_id', 'status'], 'idx_catalog_items_cat_status');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('catalog_items', function (Blueprint $table) {
            $table->dropIndex('idx_catalog_items_cat_status');
            $table->dropColumn(['category_id', 'slug', 'item_kind', 'base_duration_minutes']);
        });
    }
};

