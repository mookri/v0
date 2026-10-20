<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification dispatch columns (N1): category (routing class per the
 * packages/communication taxonomy) + dedup_key (spec identity
 * rule:event:recipient:channel, unique per tenant — the dispatcher
 * dedups idempotently under concurrent delivery). Nullable keys never
 * collide (legacy rows and unkeyed facts stay unrestricted).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('customer_notifications', function (Blueprint $table): void {
            $table->string('category', 16)->default('transactional')->after('kind');
            $table->string('dedup_key', 160)->nullable()->after('body');

            $table->unique(['tenant_id', 'dedup_key']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('customer_notifications', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'dedup_key']);
            $table->dropColumn(['category', 'dedup_key']);
        });
    }
};
