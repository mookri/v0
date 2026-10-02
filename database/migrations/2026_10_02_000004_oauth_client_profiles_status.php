<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profile lifecycle status (client revoke disables the profile row;
 * deletion resurfaces nothing — profiles are history).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('oauth_client_profiles', function (Blueprint $table): void {
            $table->string('status', 16)->default('active')->after('rate_tier');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('oauth_client_profiles', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
