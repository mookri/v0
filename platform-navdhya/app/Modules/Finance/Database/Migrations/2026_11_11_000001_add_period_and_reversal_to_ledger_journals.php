<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $conn = 'tenant_shared';

        Schema::connection($conn)->table('ledger_journals', function (Blueprint $table): void {
            $table->string('accounting_period', 7)->nullable()->after('posted_at');
            $table->ulid('reverses_journal_id')->nullable()->after('status');
            $table->ulid('reversed_by_journal_id')->nullable()->after('reverses_journal_id');
            $table->timestamp('reversed_at')->nullable()->after('reversed_by_journal_id');

            $table->index(['tenant_id', 'accounting_period'], 'idx_ledger_journals_period');
            $table->index(['tenant_id', 'reverses_journal_id'], 'idx_ledger_journals_reverses');
        });

        Schema::connection($conn)->create('accounting_period_locks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('accounting_period', 7);
            $table->string('status', 16)->default('OPEN');
            $table->string('locked_by', 64)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('lock_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'accounting_period'], 'uq_accounting_period_lock');
        });
    }

    public function down(): void
    {
        $conn = 'tenant_shared';

        Schema::connection($conn)->dropIfExists('accounting_period_locks');

        Schema::connection($conn)->table('ledger_journals', function (Blueprint $table): void {
            $table->dropIndex('idx_ledger_journals_period');
            $table->dropIndex('idx_ledger_journals_reverses');
            $table->dropColumn([
                'accounting_period',
                'reverses_journal_id',
                'reversed_by_journal_id',
                'reversed_at',
            ]);
        });
    }
};

