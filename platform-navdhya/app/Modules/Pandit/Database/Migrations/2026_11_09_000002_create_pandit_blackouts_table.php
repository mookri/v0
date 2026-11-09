<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pandit blackouts (tenant_shared): periods when a pandit is unavailable
 * for ritual bookings (personal time off, travel, festivals, admin locks).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_blackouts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('pandit_id');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason', 500)->nullable();
            $table->string('type', 32)->default('PERSONAL'); // PERSONAL, TRAVEL, FESTIVAL, ADMIN_BLOCK
            $table->timestamps();

            $table->foreign('pandit_id')->references('id')->on('pandit_profiles')->onDelete('cascade');
            $table->index(['tenant_id', 'pandit_id']);
            $table->index(['tenant_id', 'pandit_id', 'starts_at', 'ends_at']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('pandit_blackouts');
    }
};

