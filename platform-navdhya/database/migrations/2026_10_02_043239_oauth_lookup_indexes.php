<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lookup index on the stock Passport tables (our operational reads):
 * token revocation/introspection/cascade keys off user + client.
 * Additive index only — the vendor-published table shapes are untouched.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('oauth_access_tokens', function (Blueprint $table): void {
            $table->index(['user_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('oauth_access_tokens', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'client_id']);
        });
    }
};
