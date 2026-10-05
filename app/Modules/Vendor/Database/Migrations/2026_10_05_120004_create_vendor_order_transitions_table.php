<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor order transitions (P12): append-only lifecycle audit — every
 * place/advance/cancel writes one row (from → to with the acting user).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('vendor_order_transitions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('order_id');
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('vendor_orders')->onDelete('cascade');
            $table->index(['tenant_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->drop('vendor_order_transitions');
    }
};
