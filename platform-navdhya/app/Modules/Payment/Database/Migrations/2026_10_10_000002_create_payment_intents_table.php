<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment intents (tenant_shared, B3): one row per booking checkout —
 * the authoritative amount snapshot (never re-priced) plus the provider
 * order/payment ids the webhook verifies against. Unique per
 * (tenant, booking): a second :confirm can never mint a second intent
 * or order. Deliberately no BelongsToTenant global scope — the webhook
 * resolves the tenant FROM this row (see the model docblock).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('payment_intents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('booking_id', 26);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 16)->default('PENDING');
            $table->string('provider', 16);
            $table->boolean('test_mode')->default(true);
            $table->string('provider_order_id', 64);
            $table->string('provider_payment_id', 64)->nullable();
            $table->json('quote_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'booking_id']);
            $table->index('provider_order_id');
            $table->index('provider_payment_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('payment_intents');
    }
};
