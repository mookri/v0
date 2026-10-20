<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supersession marker (M2): one live challenge per
 * (tenant_id, purpose, identifier) — issuing a new challenge stamps the
 * previous one's superseded_at instead of deleting it, so the 5/hour
 * issuance cap keeps counting every send while verification only ever
 * accepts an active row.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('customer_otp_challenges', function (Blueprint $table): void {
            $table->timestamp('superseded_at')->nullable()->after('max_attempts');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('customer_otp_challenges', function (Blueprint $table): void {
            $table->dropColumn('superseded_at');
        });
    }
};
