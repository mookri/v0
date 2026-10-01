<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OTP challenges for two-phase submit (onboarding stage 2 port): hashed
 * codes only (never plaintext), attempt caps and hourly rate limits
 * enforced in the service. Tenant-scoped like everything else.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('otp_challenges', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('identifier', 64);
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts');
            $table->timestamps();

            $table->index(['tenant_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('otp_challenges');
    }
};
