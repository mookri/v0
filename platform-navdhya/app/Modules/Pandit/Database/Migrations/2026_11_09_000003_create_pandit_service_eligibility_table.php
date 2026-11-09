<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pandit service eligibility (tenant_shared): ritual SKU qualifications and certifications.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_service_eligibility', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('pandit_id');
            $table->string('service_sku', 64);
            $table->boolean('is_qualified')->default(true);
            $table->string('qualification_level', 32)->default('STANDARD');
            $table->dateTime('certified_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('pandit_id')->references('id')->on('pandit_profiles')->onDelete('cascade');
            $table->index(['tenant_id', 'pandit_id']);
            $table->index(['tenant_id', 'service_sku']);
            $table->unique(['tenant_id', 'pandit_id', 'service_sku']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('pandit_service_eligibility');
    }
};
