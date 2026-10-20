<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checkout snapshot columns (B3): the confirmed amount is snapshotted
 * from the QuoteCalculator quote at :confirm time so later views (B4,
 * customer UI) never re-price a settled booking. The authoritative
 * snapshot lives on payment_intents; these columns are the booking's
 * own copy for lifecycle display. Nullable: DRAFT bookings have no
 * price yet.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('bookings', function (Blueprint $table): void {
            $table->unsignedBigInteger('total_minor')->nullable()->after('address_snapshot');
            $table->char('currency', 3)->nullable()->after('total_minor');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['total_minor', 'currency']);
        });
    }
};
