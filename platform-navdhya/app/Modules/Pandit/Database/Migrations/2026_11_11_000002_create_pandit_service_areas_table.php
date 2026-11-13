<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pandit service areas (tenant_shared): geographic serviceability and operational territories.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_service_areas', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('pandit_id');
            $table->string('region_id', 26)->index();
            $table->string('region_name', 100);
            $table->boolean('is_primary')->default(true);
            $table->unsignedInteger('max_distance_km')->default(25);
            $table->unsignedInteger('travel_fee_minor')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('pandit_id')->references('id')->on('pandit_profiles')->onDelete('cascade');
            $table->index(['tenant_id', 'pandit_id']);
            $table->index(['tenant_id', 'region_id']);
            $table->unique(['tenant_id', 'pandit_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('pandit_service_areas');
    }
};

