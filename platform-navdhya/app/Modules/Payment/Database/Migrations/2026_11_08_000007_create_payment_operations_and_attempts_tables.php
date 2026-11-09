<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $conn = 'tenant_shared';

        // 1. Payment Operations
        Schema::connection($conn)->create('payment_operations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('booking_id', 26);
            $table->string('operation_type', 32); // AUTHORIZE, CAPTURE, REFUND, VOID, DISPUTE
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 32)->default('PENDING'); // PENDING, PROCESSING, SUCCEEDED, FAILED
            $table->string('provider_code', 32)->default('RAZORPAY');
            $table->string('provider_operation_id', 64)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'booking_id', 'status'], 'idx_pay_ops_booking_status');
            $table->index(['tenant_id', 'provider_code', 'provider_operation_id'], 'idx_pay_ops_provider_op');
        });

        // 2. Payment Attempts
        Schema::connection($conn)->create('payment_attempts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('payment_operation_id');
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status', 32)->default('INITIATED'); // INITIATED, SUCCEEDED, FAILED, TIMED_OUT
            $table->json('provider_payload_json')->nullable();
            $table->json('provider_response_json')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'payment_operation_id'], 'idx_pay_attempts_op');
        });
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        Schema::connection($conn)->dropIfExists('payment_attempts');
        Schema::connection($conn)->dropIfExists('payment_operations');
    }
};
