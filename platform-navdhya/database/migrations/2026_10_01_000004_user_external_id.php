<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External identity linkage (token bridge): frontend-issued identity ids
 * (opaque strings) map to central users via external_id. Null until first
 * HMAC-authenticated request links them; unique when present.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->string('external_id', 64)->nullable()->unique()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->dropUnique(['external_id']);
            $table->dropColumn('external_id');
        });
    }
};
