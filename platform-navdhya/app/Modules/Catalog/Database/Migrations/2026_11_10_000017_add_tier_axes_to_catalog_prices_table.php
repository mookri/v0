<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $conn = 'tenant_shared';
        if (Schema::connection($conn)->hasTable('catalog_prices')) {
            Schema::connection($conn)->table('catalog_prices', function (Blueprint $table) use ($conn): void {
                if (! Schema::connection($conn)->hasColumn('catalog_prices', 'package_tier_code')) {
                    $table->string('package_tier_code', 32)->nullable()->after('pandit_id');
                }
                if (! Schema::connection($conn)->hasColumn('catalog_prices', 'qualification_tier_code')) {
                    $table->string('qualification_tier_code', 32)->nullable()->after('package_tier_code');
                }
                if (! Schema::connection($conn)->hasColumn('catalog_prices', 'version')) {
                    $table->unsignedInteger('version')->default(1)->after('price_minor');
                }
                if (! Schema::connection($conn)->hasColumn('catalog_prices', 'is_current')) {
                    $table->boolean('is_current')->default(true)->after('version');
                }

                $table->index(
                    ['tenant_id', 'sku', 'market', 'package_tier_code', 'qualification_tier_code'],
                    'idx_catalog_prices_two_axis'
                );
                $table->index(['tenant_id', 'sku', 'is_current'], 'idx_catalog_prices_current');
            });
        }
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        if (Schema::connection($conn)->hasTable('catalog_prices')) {
            Schema::connection($conn)->table('catalog_prices', function (Blueprint $table) use ($conn): void {
                $table->dropIndex('idx_catalog_prices_two_axis');
                $table->dropIndex('idx_catalog_prices_current');

                if (Schema::connection($conn)->hasColumn('catalog_prices', 'is_current')) {
                    $table->dropColumn('is_current');
                }
                if (Schema::connection($conn)->hasColumn('catalog_prices', 'version')) {
                    $table->dropColumn('version');
                }
                if (Schema::connection($conn)->hasColumn('catalog_prices', 'qualification_tier_code')) {
                    $table->dropColumn('qualification_tier_code');
                }
                if (Schema::connection($conn)->hasColumn('catalog_prices', 'package_tier_code')) {
                    $table->dropColumn('package_tier_code');
                }
            });
        }
    }
};

