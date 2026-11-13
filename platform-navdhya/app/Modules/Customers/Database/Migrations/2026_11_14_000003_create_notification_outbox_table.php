<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transactional notification outbox table (E-CM-03 / Task T-CM03-01).
 * Decouples business transaction commits from network delivery attempts.
 * event_id is UNIQUE to guarantee that replayed events cannot produce duplicate outbox records.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('notification_outbox', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('event_id', 64)->unique();
            $table->string('aggregate_type', 64);
            $table->string('aggregate_id', 64);
            $table->string('event_type', 64);
            $table->json('payload_json');
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at'], 'comm_outbox_status_next_idx');
            $table->index(['tenant_id', 'aggregate_type', 'aggregate_id'], 'comm_outbox_tenant_aggregate_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('notification_outbox');
    }
};

