<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer OTP challenges (central): like onboarding's otp_challenges but
 * carrying a purpose (login/signup/verify_*) plus an opaque JSON payload
 * (signup intent travels with the challenge until verification creates
 * the account). Central + scopeless on purpose: public auth routes run
 * with no resolved tenant context, so BelongsToTenant models cannot be
 * used here — every read predicates tenant_id explicitly.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('customer_otp_challenges', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('purpose', 24);
            $table->string('identifier', 64);
            $table->string('code_hash', 64);
            $table->json('payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->timestamps();

            $table->index(['tenant_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('customer_otp_challenges');
    }
};
