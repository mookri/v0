<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support tickets (tenant_shared, customer side): OPEN/CLOSED states,
 * capped open count per customer. Staff queue/assignment/SLA is a
 * separate surface (mirrors the onboarding review pattern) — this table
 * carries only what the customer surface needs.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('support_tickets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('subject', 190);
            $table->string('category', 32);
            $table->string('status', 16)->default('OPEN');
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('support_tickets');
    }
};
