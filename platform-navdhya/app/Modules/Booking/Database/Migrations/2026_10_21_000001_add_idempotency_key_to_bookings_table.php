<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency-Key on bookings (BACKEND-RCA 2026-10-21, finding W1).
 *
 * The header was REQUIRED on booking creation but never stored: the
 * replay check was a 60-second, payload-shaped heuristic, so a client
 * retry after a minute created a SECOND booking, and two legitimate
 * bookings for the same slot inside the minute collapsed onto the
 * first one.
 *
 * The key is now stored, scoped to (tenant, user), with the request
 * hash beside it: the same key with the same payload is a replay, the
 * same key with a different payload is a key reuse and is refused.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('bookings', function (Blueprint $table): void {
            $table->string('idempotency_key', 128)->nullable()->after('service_sku');
            $table->string('idempotency_hash', 64)->nullable()->after('idempotency_key');
            $table->unique(['tenant_id', 'user_id', 'idempotency_key'], 'bookings_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('bookings', function (Blueprint $table): void {
            $table->dropUnique('bookings_idempotency_key_unique');
            $table->dropColumn(['idempotency_key', 'idempotency_hash']);
        });
    }
};
