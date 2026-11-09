<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add status_detail and refund_amount_minor to bookings table (tenant_shared).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('bookings', function (Blueprint $table): void {
            $table->string('status_detail', 64)->nullable()->after('status');
            $table->unsignedBigInteger('refund_amount_minor')->nullable()->after('status_detail');

            $table->index(['tenant_id', 'status_detail']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'status_detail']);
            $table->dropColumn(['status_detail', 'refund_amount_minor']);
        });
    }
};

