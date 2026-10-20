<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MFA columns on central users (SEC-004 §1.4): TOTP secret (application
 * encrypted, never plaintext at rest), enabled flag, hashed single-use
 * recovery codes, enrollment timestamp. Secrets are set at enroll and
 * activated at confirm — abandoned enrollments sit disabled and are
 * overwritten by re-enrollment.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->text('mfa_secret')->nullable()->after('phone_verified_at');
            $table->boolean('mfa_enabled')->default(false)->after('mfa_secret');
            $table->json('mfa_recovery_hashes')->nullable()->after('mfa_enabled');
            $table->timestamp('mfa_enrolled_at')->nullable()->after('mfa_recovery_hashes');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->dropColumn(['mfa_secret', 'mfa_enabled', 'mfa_recovery_hashes', 'mfa_enrolled_at']);
        });
    }
};
