<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking Commands (Task 1.2.1, Section 4):
 * Deduplication and idempotency tracking for all booking mutations.
 * Unique constraint on (tenant_id, actor_id, verb, idempotency_key).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('booking_commands', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('actor_id', 64);
            $table->string('verb', 32);
            $table->string('idempotency_key', 128);
            $table->string('canonical_hash', 64);
            $table->string('target_id', 26)->nullable();
            $table->string('outcome_status', 24)->default('PROCESSING');
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'actor_id', 'verb', 'idempotency_key'],
                'uq_booking_commands_idempotency'
            );
            $table->index(['tenant_id', 'outcome_status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('booking_commands');
    }
};
