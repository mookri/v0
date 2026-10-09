<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MFA attempt lockout + TOTP replay state (M4 / SEC-004 §1.4): a shared
 * per-user failure counter (5 failures → 15 min locked_until, enforced by
 * MfaService) plus the last accepted TOTP step so a code can never be
 * burned twice. Counters reset on success or revocation.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->unsignedInteger('mfa_failed_attempts')->default(0)->after('mfa_enrolled_at');
            $table->timestamp('mfa_locked_until')->nullable()->after('mfa_failed_attempts');
            $table->unsignedBigInteger('mfa_last_used_step')->nullable()->after('mfa_locked_until');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->dropColumn(['mfa_failed_attempts', 'mfa_locked_until', 'mfa_last_used_step']);
        });
    }
};
