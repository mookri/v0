<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referral attributions (tenant_shared, append-only): one row per
 * referred account, written at signup verify when the payload carries a
 * valid foreign code. Invalid and self codes are ignored, never errors.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('referral_attributions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('referrer_user_id');
            $table->unsignedBigInteger('referred_user_id')->unique();
            $table->string('code', 16);
            $table->timestamps();

            $table->index(['tenant_id', 'referrer_user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('referral_attributions');
    }
};
