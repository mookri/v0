<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer activities (tenant_shared): the customer-safe read model for
 * account history. Written by customers-module mutations only (never by
 * other modules, never read from kernel audit_logs — hash-chained audit
 * rows are staff-only and must not leak through a customer surface).
 * Labels are fixed templates rendered at write time: no raw payloads,
 * nothing to sanitize at read.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('customer_activities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('kind', 32);
            $table->string('label', 280);
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('customer_activities');
    }
};
