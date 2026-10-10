<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I1: device metadata for actor sessions — user agent, IP, and
 * user-settable label so the security page can display "Chrome on
 * Windows • 203.0.113.45 • Work laptop" and the user can revoke
 * specific devices.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('actor_sessions', function (Blueprint $table): void {
            $table->text('user_agent')->nullable()->after('expires_at');
            $table->string('ip_address', 45)->nullable()->after('user_agent');
            $table->string('label', 120)->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('actor_sessions', function (Blueprint $table): void {
            $table->dropColumn(['user_agent', 'ip_address', 'label']);
        });
    }
};