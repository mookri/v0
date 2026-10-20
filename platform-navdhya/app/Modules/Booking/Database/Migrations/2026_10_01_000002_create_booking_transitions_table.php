<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking transitions (tenant_shared, append-only): every lifecycle
 * change writes a row (reschedules included — from == to with a reason).
 * The history endpoint reads this table, never the bookings row.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('booking_transitions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('booking_id', 26);
            $table->string('from_status', 24);
            $table->string('to_status', 24);
            $table->string('reason', 64)->nullable();
            $table->string('actor_id', 64);
            $table->timestamps();

            $table->index(['tenant_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('booking_transitions');
    }
};
