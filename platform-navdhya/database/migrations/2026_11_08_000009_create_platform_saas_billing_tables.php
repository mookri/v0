<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $conn = 'central';

        // 1. Platform Subscription Plans
        Schema::connection($conn)->create('platform_subscription_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 64)->unique(); // STARTER, GROWTH, ENTERPRISE
            $table->string('name', 128);
            $table->string('billing_interval', 16)->default('MONTHLY'); // MONTHLY, YEARLY
            $table->bigInteger('price_minor');
            $table->char('currency', 3)->default('INR');
            $table->json('features_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tenant Subscriptions
        Schema::connection($conn)->create('tenant_subscriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('plan_id');
            $table->string('status', 32)->default('ACTIVE'); // ACTIVE, PAST_DUE, CANCELLED, TRIALING
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('provider_subscription_id', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_tenant_sub_status');
        });

        // 3. Platform Invoices (Gapless SaaS Billing to Tenants)
        Schema::connection($conn)->create('platform_invoices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('subscription_id')->nullable();
            $table->string('invoice_number', 64)->unique();
            $table->bigInteger('subtotal_minor');
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('total_minor');
            $table->char('currency', 3)->default('INR');
            $table->string('status', 32)->default('PAID'); // DRAFT, ISSUED, PAID, VOID
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_platform_inv_tenant_status');
        });
    }

    public function down(): void
    {
        $conn = 'central';
        Schema::connection($conn)->dropIfExists('platform_invoices');
        Schema::connection($conn)->dropIfExists('tenant_subscriptions');
        Schema::connection($conn)->dropIfExists('platform_subscription_plans');
    }
};
