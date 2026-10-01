<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookings (tenant_shared, ADR-005): the customer transaction row. Only
 * this module mutates status (BookingLifecycle); creation endpoints land
 * with the /book handoff slice — rows are seeded by tests/factories until
 * then. version is the optimistic lock for reschedule.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('bookings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('service_sku', 64)->nullable();
            $table->string('status', 24)->default('DRAFT');
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->json('address_snapshot')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('bookings');
    }
};
