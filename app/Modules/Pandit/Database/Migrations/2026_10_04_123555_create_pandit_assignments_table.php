<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pandit assignments (tenant_shared): links pandits to bookings.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('pandit_id');
            $table->ulid('booking_id');
            $table->string('status', 24)->default('PENDING'); // PENDING, CONFIRMED, DECLINED, COMPLETED, CANCELLED
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('decline_reason', 500)->nullable();
            $table->unsignedBigInteger('compensation_minor')->nullable();
            $table->timestamps();

            // pandit_id: same-module FK (cascades with the profile).
            // booking_id: soft reference only — the bookings table belongs
            // to the Booking module (ADR-005) and cross-module rows are
            // never FK-constrained here (same posture as
            // catalog_prices.pandit_id). Read through the ReadsBookings port.
            $table->foreign('pandit_id')->references('id')->on('pandit_profiles')->onDelete('cascade');
            $table->index(['tenant_id', 'pandit_id']);
            $table->index(['tenant_id', 'booking_id']);
            $table->index(['tenant_id', 'pandit_id', 'status']);
            $table->unique(['pandit_id', 'booking_id']); // one assignment per pandit per booking
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('pandit_assignments');
    }
};
