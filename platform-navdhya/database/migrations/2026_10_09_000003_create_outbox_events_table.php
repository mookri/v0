<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transactional outbox (F2): producers append events on the SAME tenant
 * connection inside their own transaction, so the fact and the state
 * change commit or roll back together (no dual-write). `outbox:drain`
 * claims pending rows under a row lock and marks dispatched_at in the
 * same claim transaction, giving exactly-once dispatch to registered
 * handlers; attempts/last_error record retries for ops.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('outbox_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('aggregate_type', 64);
            $table->string('aggregate_id', 64);
            $table->string('event_type', 64);
            $table->json('payload');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('last_error', 500)->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();

            // Drain claim scan (pending + under the retry cap).
            $table->index(['dispatched_at', 'attempts'], 'outbox_pending_idx');
            // Aggregate history reads (per booking/customer/…).
            $table->index(['tenant_id', 'aggregate_type', 'aggregate_id'], 'outbox_aggregate_idx');
            // Dispatch order per event type.
            $table->index(['event_type', 'created_at'], 'outbox_type_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('outbox_events');
    }
};
