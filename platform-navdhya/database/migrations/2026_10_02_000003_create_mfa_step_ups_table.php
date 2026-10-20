<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MFA step-up proofs (central): one row per successful step-up
 * verification (MFA verify endpoint). The requires_acr middleware
 * treats a row younger than 15 minutes as ACR 2; older rows are swept
 * after 24 hours (forensics window, never authority).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('mfa_step_ups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('verified_at');
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('mfa_step_ups');
    }
};
