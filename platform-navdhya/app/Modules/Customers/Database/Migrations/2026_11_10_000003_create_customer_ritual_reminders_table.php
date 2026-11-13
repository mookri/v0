<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devotee ritual reminders (E1 / E3):
 * Stores anchor_rule JSON as the authoritative source of truth (Tithi, Masa, Paksha).
 * next_occurrence_at is an indexed operational cursor recomputed deterministically on writes/fires.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('customer_ritual_reminders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('family_member_id', 26)->nullable();
            $table->string('type', 32); // SHRADDHA | LUNAR_BIRTHDAY | SOLAR_BIRTHDAY | WEDDING_ANNIVERSARY | CUSTOM_TITHI
            $table->string('title', 120);
            $table->json('anchor_rule'); // Authoritative rule: tithi, paksha, masa, tradition, etc.
            $table->json('advance_days'); // Advance alert slots, e.g. [7, 3, 1, 0]
            $table->dateTime('next_occurrence_at')->nullable(); // Operational cursor
            $table->string('last_fired_period', 191)->nullable(); // Deduplication marker, e.g. "{reminder_id}:{year}-{masa}-{paksha}-{tithi}:{daysUntil}d"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'is_active', 'next_occurrence_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('customer_ritual_reminders');
    }
};
