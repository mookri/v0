<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arrival evidence table (tenant_shared, TS-01 / ADR-0060).
 *
 * Intentionally stores NO GPS, latitude, longitude, or geofence columns
 * to respect worker privacy unless an explicit opt-in legal basis exists.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_arrivals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('booking_id', 26)->unique();
            $table->string('assignment_id', 26)->nullable();
            $table->string('pandit_id', 26);
            $table->timestamp('arrived_at');
            $table->string('recorded_by_actor_id', 64);
            $table->string('source_channel', 32); // partner_app | devotee_corroboration | coordinator | ops_override
            $table->timestamp('corroborated_at')->nullable();
            $table->string('corroborated_by_id', 64)->nullable();
            $table->string('corroboration_channel', 32)->nullable();
            $table->string('dispute_state', 24)->default('NONE');
            $table->integer('delta_minutes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'pandit_id']);
            $table->index(['tenant_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('pandit_arrivals');
    }
};

