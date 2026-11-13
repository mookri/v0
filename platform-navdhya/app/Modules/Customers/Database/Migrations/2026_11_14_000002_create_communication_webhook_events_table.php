<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public notification webhook deduplication registry (E-CM-02 / Task T-CM02-03).
 * Ensures that retried provider status callbacks (Meta / MSG91) are deduplicated
 * before state mutation occurs.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('communication_webhook_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('provider', 32);
            $table->string('provider_message_id', 190);
            $table->string('event_type', 32);
            $table->timestamp('occurred_at');
            $table->string('payload_hash', 64);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['provider', 'provider_message_id', 'event_type', 'occurred_at'],
                'comm_webhook_provider_msg_event_time_uq'
            );
            $table->index(['provider', 'provider_message_id'], 'comm_webhook_provider_msg_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('communication_webhook_events');
    }
};

