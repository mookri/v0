<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $conn = 'central';

        // 1. Central Provider Operation Routes
        Schema::connection($conn)->create('provider_operation_routes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('provider_code', 32); // RAZORPAY, STRIPE
            $table->string('provider_operation_id', 64); // pay_xxx, order_xxx, rfnd_xxx
            $table->string('tenant_id', 26);
            $table->string('connection_name', 64);
            $table->timestamps();

            $table->unique(['provider_code', 'provider_operation_id'], 'uq_prov_op_routes');
            $table->index('tenant_id', 'idx_prov_op_routes_tenant');
        });

        // 2. Central Provider Event Inbox
        Schema::connection($conn)->create('provider_event_inbox', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('provider_code', 32);
            $table->string('event_id', 128);
            $table->string('event_type', 64);
            $table->longText('raw_payload');
            $table->json('headers_json');
            $table->string('signature', 255)->nullable();
            $table->string('tenant_id', 26)->nullable();
            $table->string('status', 32)->default('RECEIVED'); // RECEIVED, VERIFIED, PROCESSED, FAILED, DUPLICATE
            $table->text('error_detail')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_code', 'event_id'], 'uq_prov_inbox_event');
            $table->index(['provider_code', 'status'], 'idx_prov_inbox_status');
        });
    }

    public function down(): void
    {
        $conn = 'central';
        Schema::connection($conn)->dropIfExists('provider_event_inbox');
        Schema::connection($conn)->dropIfExists('provider_operation_routes');
    }
};
