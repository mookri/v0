<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outbox Dead Letter Queue (DLQ) table (T-JYAUT-00e / D-16).
 * Quarantines outbox events that have failed MAX_ATTEMPTS retries,
 * preserving payload, failure forensics, and enabling replay.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('outbox_dead_letters', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('original_event_id', 26);
            $table->string('aggregate_type', 64);
            $table->string('aggregate_id', 64);
            $table->string('event_type', 64);
            $table->json('payload');
            $table->unsignedTinyInteger('attempts');
            $table->string('last_error', 500)->nullable();
            $table->timestamp('quarantined_at')->useCurrent();
            $table->timestamp('replayed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'quarantined_at'], 'outbox_dlq_tenant_quarantine_idx');
            $table->index(['original_event_id'], 'outbox_dlq_original_id_idx');
            $table->index(['event_type', 'quarantined_at'], 'outbox_dlq_type_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('outbox_dead_letters');
    }
};

