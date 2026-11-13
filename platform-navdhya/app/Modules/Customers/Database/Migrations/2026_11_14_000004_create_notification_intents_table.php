<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification intent registry (E-CM-03 / Task T-CM03-02).
 * Represents one business moment × one recipient before channel derivation.
 * dedup_key is NOT NULL and unique per tenant to guarantee idempotency.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('notification_intents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('recipient_ref', 128);
            $table->string('subject_type', 64);
            $table->string('subject_id', 64);
            $table->string('category', 32);
            $table->string('priority', 16)->default('normal');
            $table->string('locale', 12)->default('en_IN');
            $table->string('dedup_key', 64);
            $table->string('policy_version', 32)->default('2026.1');
            $table->string('status', 32)->default('planned');
            $table->timestamps();

            $table->unique(['tenant_id', 'dedup_key'], 'comm_intents_tenant_dedup_uq');
            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'comm_intents_tenant_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('notification_intents');
    }
};

