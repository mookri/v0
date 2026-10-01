<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Segregation-of-duties rules (SEC-011, central): platform defaults carry
 * NULL tenant_id; tenant overrides carry their tenant_id and win by code.
 * Rows are read with explicit tenant predicates — never BelongsToTenant,
 * whose global scope would hide the platform-default rows.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('sod_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->nullable();
            $table->string('code', 64);
            $table->string('name', 190);
            $table->string('kind', 16)->default('conflict');
            $table->json('role_set')->nullable();
            $table->json('permission_set')->nullable();
            $table->string('severity', 16)->default('block');
            $table->string('status', 16)->default('active');
            $table->timestamp('operator_consented_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id']);
            $table->index(['code']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('sod_rules');
    }
};
