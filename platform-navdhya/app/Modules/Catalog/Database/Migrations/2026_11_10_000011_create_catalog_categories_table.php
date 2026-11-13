<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('catalog_categories', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->nullable()->index();
            $table->string('tenancy_key', 64)->default('__PLATFORM_GLOBAL__');
            $table->string('parent_id', 26)->nullable()->index();
            $table->string('path', 500)->default('/');
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('slug', 100);
            $table->string('name', 190);
            $table->string('kind', 50)->default('RITUALS'); // RITUALS | HAVANS | SANSKARS | ASTROLOGY | TEMPLE_PUJAS
            $table->string('provenance', 50)->default('AUTHENTIC'); // AUTHENTIC | INFERRED | SEEDED
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 30)->default('ACTIVE'); // ACTIVE | INACTIVE | ARCHIVED
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenancy_key', 'slug'], 'uq_catalog_category_tenancy_slug');
            $table->index(['tenant_id', 'kind', 'status'], 'idx_catalog_category_lookup');
            $table->index(['path'], 'idx_catalog_category_path');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('catalog_categories');
    }
};

