<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pandit assignment rules (tenant_shared): auto-accept criteria, distance boundaries,
 * and response SLA window before automatic reassignment.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_assignment_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('pandit_id');
            $table->json('auto_accept_skus')->nullable();
            $table->unsignedInteger('max_distance_km')->nullable();
            $table->unsignedInteger('response_sla_hours')->default(24);
            $table->timestamps();

            $table->foreign('pandit_id')->references('id')->on('pandit_profiles')->onDelete('cascade');
            $table->index(['tenant_id', 'pandit_id']);
            $table->unique(['tenant_id', 'pandit_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('pandit_assignment_rules');
    }
};

