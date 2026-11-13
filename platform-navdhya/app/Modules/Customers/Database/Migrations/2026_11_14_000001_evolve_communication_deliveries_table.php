<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Communications delivery table evolution (E-CM-01 / C-09 / C-10).
 * Renames notification_deliveries to communication_deliveries,
 * widens status to varchar(32) to prevent truncation of PROVIDER_ACCEPTED (17 chars)
 * and DELIVERY_UNKNOWN (16 chars), enforces NOT NULL on dedup_key,
 * and adds full lifecycle timestamps, intent linkage, idempotency, and cost metrics.
 */
return new class () extends Migration {
    public function up(): void
    {
        $schema = Schema::connection('tenant_shared');

        // 1. Rename table if old name exists
        if ($schema->hasTable('notification_deliveries') && ! $schema->hasTable('communication_deliveries')) {
            $schema->rename('notification_deliveries', 'communication_deliveries');
        }

        // 2. Backfill null dedup_keys prior to non-null constraint
        if ($schema->hasTable('communication_deliveries')) {
            $connection = DB::connection('tenant_shared');
            $driver = $connection->getDriverName();
            $expr = $driver === 'sqlite' ? "'legacy_backfill_' || id" : "CONCAT('legacy_backfill_', id)";
            $connection
                ->table('communication_deliveries')
                ->whereNull('dedup_key')
                ->update(['dedup_key' => DB::raw($expr)]);
        }

        // 3. Widen status and enforce NOT NULL dedup_key
        $schema->table('communication_deliveries', function (Blueprint $table): void {
            $table->string('status', 32)->change();
            $table->string('dedup_key', 160)->nullable(false)->change();
        });

        // 4. Add lifecycle, routing, idempotency and telemetry columns
        $schema->table('communication_deliveries', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('communication_deliveries', 'intent_id')) {
                $table->string('intent_id', 26)->nullable()->after('id')->index();
            }
            if (! $schema->hasColumn('communication_deliveries', 'provider')) {
                $table->string('provider', 32)->nullable()->after('channel')->index();
            }
            if (! $schema->hasColumn('communication_deliveries', 'template_version_id')) {
                $table->string('template_version_id', 64)->nullable()->after('provider');
            }
            if (! $schema->hasColumn('communication_deliveries', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->after('dedup_key')->index();
            }
            if (! $schema->hasColumn('communication_deliveries', 'attempt_count')) {
                $table->unsignedInteger('attempt_count')->default(0)->after('status');
            }
            if (! $schema->hasColumn('communication_deliveries', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('attempt_count');
            }
            if (! $schema->hasColumn('communication_deliveries', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('submitted_at');
            }
            if (! $schema->hasColumn('communication_deliveries', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('accepted_at');
            }
            if (! $schema->hasColumn('communication_deliveries', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at');
            }
            if (! $schema->hasColumn('communication_deliveries', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('delivered_at');
            }
            if (! $schema->hasColumn('communication_deliveries', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('read_at');
            }
            if (! $schema->hasColumn('communication_deliveries', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable()->after('failed_at');
            }
            if (! $schema->hasColumn('communication_deliveries', 'unit_cost_cents')) {
                $table->unsignedInteger('unit_cost_cents')->nullable()->after('reconciled_at');
            }
            if (! $schema->hasColumn('communication_deliveries', 'error_code')) {
                $table->string('error_code', 64)->nullable()->after('unit_cost_cents');
            }
            if (! $schema->hasColumn('communication_deliveries', 'recipient_reference_hash')) {
                $table->string('recipient_reference_hash', 64)->nullable()->after('error_code');
            }

            $table->index(['status', 'created_at'], 'comm_deliveries_status_created_idx');
            $table->index(['provider', 'provider_message_id', 'tenant_id'], 'comm_deliveries_provider_msg_tenant_idx');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant_shared');

        if ($schema->hasTable('communication_deliveries')) {
            $schema->table('communication_deliveries', function (Blueprint $table): void {
                $table->dropIndex('comm_deliveries_status_created_idx');
                $table->dropIndex('comm_deliveries_provider_msg_tenant_idx');
                $table->dropColumn([
                    'intent_id', 'provider', 'template_version_id', 'idempotency_key',
                    'attempt_count', 'submitted_at', 'accepted_at', 'sent_at',
                    'delivered_at', 'read_at', 'failed_at', 'reconciled_at',
                    'unit_cost_cents', 'error_code', 'recipient_reference_hash',
                ]);
                $table->string('dedup_key', 160)->nullable()->change();
                $table->string('status', 16)->change();
            });

            $schema->rename('communication_deliveries', 'notification_deliveries');
        }
    }
};
