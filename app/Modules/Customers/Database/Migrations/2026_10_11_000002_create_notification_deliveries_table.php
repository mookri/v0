<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification delivery log (N3): one row per keyed channel delivery
 * (email first). The unique (tenant, dedup_key) index is the claim —
 * the dispatcher inserts pending before sending, flips to sent
 * afterwards, and a queue retry after a partial run resumes its own
 * pending row instead of re-sending (at-least-once exactly where it
 * matters). suppressed rows record why a leg never went out (no
 * address on record); exhausted retries leave pending for ops.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('notification_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('kind', 32);
            $table->string('category', 16)->default('transactional');
            $table->string('channel', 16);
            $table->string('dedup_key', 160)->nullable();
            $table->string('status', 16);
            $table->string('provider_message_id', 190)->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'dedup_key']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('notification_deliveries');
    }
};
