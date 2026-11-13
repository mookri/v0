<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widens customer_ritual_reminders.last_fired_period from string(32) to string(191) (T-JYAUT-00a / D-12).
 * Accommodates full ULID-based dedup period keys:
 * format "{reminder_id}:{year}-{masa}-{paksha}-{tithi}:{daysUntil}d" (~56 chars).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('customer_ritual_reminders', function (Blueprint $table): void {
            $table->string('last_fired_period', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('customer_ritual_reminders', function (Blueprint $table): void {
            $table->string('last_fired_period', 32)->nullable()->change();
        });
    }
};

