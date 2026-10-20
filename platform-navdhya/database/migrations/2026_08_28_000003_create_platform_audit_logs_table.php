<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log (K-025) on the tenant data connection (TEN-003 §2.2): audit
 * rows MUST commit in the same transaction as the domain change they
 * record, so they follow the tenant's db_mode connection.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->json('actor')->nullable(); // {principal_id, kind, user_id?}
            $table->string('action', 64);
            $table->json('resource')->nullable(); // {module, resource_type, resource_id} (K-012)
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('request_id', 64)->nullable();
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64);
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['tenant_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('audit_logs');
    }
};
