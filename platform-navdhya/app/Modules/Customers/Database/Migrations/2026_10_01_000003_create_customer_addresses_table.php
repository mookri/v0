<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer address book (tenant_shared): up to 20 rows per customer,
 * exactly one default. Addresses exist for future booking fulfillment —
 * no foreign keys out (bookings snapshot the address at creation later).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('customer_addresses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('label', 64);
            $table->string('line1', 190);
            $table->string('line2', 190)->nullable();
            $table->string('city', 120);
            $table->string('state', 120);
            $table->string('postal_code', 24);
            $table->json('geo')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('customer_addresses');
    }
};
