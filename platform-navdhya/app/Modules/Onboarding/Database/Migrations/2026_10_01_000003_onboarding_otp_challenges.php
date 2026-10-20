<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OTP challenges for two-phase submit (onboarding stage 2 port),
 * driven by the shared OtpChallengeManager: attempt caps, the
 * compounding resend schedule and the fresh-challenge gate all live in
 * the manager — this table only stores the state. `code_hash` is
 * nullable because MSG91 widget challenges are generated and judged in
 * the browser (rows carry `provider_ref` instead); `purpose` scopes
 * the shared table to the submit flow today and any future
 * tenant-side OTP flow tomorrow. Tenant-scoped like everything else.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('otp_challenges', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('purpose', 32)->default('partner_submit');
            $table->string('identifier', 64);
            $table->string('code_hash', 64)->nullable();
            $table->string('provider', 24)->default('msg91_widget');
            $table->text('provider_ref')->nullable();
            $table->string('status', 16)->default('sent');
            $table->timestamp('expires_at');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts');
            $table->unsignedInteger('resend_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('otp_challenges');
    }
};
