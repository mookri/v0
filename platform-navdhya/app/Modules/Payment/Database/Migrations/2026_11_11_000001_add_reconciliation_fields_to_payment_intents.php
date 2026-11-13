<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('payment_intents', function (Blueprint $table): void {
            $table->timestamp('reconciled_at')->nullable()->after('quote_snapshot');
            $table->unsignedInteger('reconcile_attempts')->default(0)->after('reconciled_at');
            $table->string('last_reconcile_error', 255)->nullable()->after('reconcile_attempts');

            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('payment_intents', function (Blueprint $table): void {
            $table->dropIndex(['status', 'updated_at']);
            $table->dropColumn(['reconciled_at', 'reconcile_attempts', 'last_reconcile_error']);
        });
    }
};

