<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phone identity on central users (customer auth prereq, ADR-0005):
 * phone-first login/OTP needs a nullable unique phone plus its own
 * verification timestamp (email_verified_at is not reused — the two
 * channels verify independently).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->string('phone', 24)->nullable()->unique()->after('email_verified_at');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'phone_verified_at']);
        });
    }
};
