<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Session-bound access tokens (A-SEC-004-6): the OP session id travels
 * on the access-token ROW for every session-bound mint (auth-code
 * exchange under an openid ceremony; NULL for machine tokens — client
 * credentials never die with a browser session). SessionTerminator
 * revokes the whole lineage by this column at logout, closing the gap
 * between "the OP session is gone" and "the tokens it minted still
 * validate for up to 15 minutes".
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('oauth_access_tokens', function (Blueprint $table): void {
            $table->string('sid', 64)->nullable()->after('scopes');
            $table->index('sid');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('oauth_access_tokens', function (Blueprint $table): void {
            $table->dropIndex(['sid']);
            $table->dropColumn('sid');
        });
    }
};
