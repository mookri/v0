<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pandit availability (tenant_shared): recurring weekly schedule + one-off overrides.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_availability', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('pandit_id');
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday ... 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('slot_duration_minutes')->default(60);
            $table->boolean('is_recurring')->default(true);
            $table->date('specific_date')->nullable(); // for one-off overrides
            $table->timestamps();

            $table->foreign('pandit_id')->references('id')->on('pandit_profiles')->onDelete('cascade');
            $table->index(['tenant_id', 'pandit_id']);
            $table->index(['tenant_id', 'pandit_id', 'day_of_week']);
            $table->index(['tenant_id', 'pandit_id', 'specific_date']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('pandit_availability');
    }
};
