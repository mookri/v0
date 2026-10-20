<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relying-party logout registration (SEC-004 / A-SEC-004-5, central):
 * two OIDC RP-Initiated / Back-Channel Logout client fields on the
 * profile extension of oauth_clients —
 *
 *  - post_logout_redirect_uris: exact-match allowlist the end-session
 *    endpoint may redirect to after terminating the OP session (never
 *    derived from redirect_uris: logout targets are registered
 *    explicitly, never implied by a callback).
 *  - backchannel_logout_uri: where this RP receives
 *    backchannel_logout_token POSTs when any OP session that
 *    authorized it ends.
 *
 * Both nullable: absent means "no registration", which fails closed at
 * every consumer (no redirect, no delivery attempt).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('oauth_client_profiles', function (Blueprint $table): void {
            $table->json('post_logout_redirect_uris')->nullable();
            $table->string('backchannel_logout_uri', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('oauth_client_profiles', function (Blueprint $table): void {
            $table->dropColumn(['post_logout_redirect_uris', 'backchannel_logout_uri']);
        });
    }
};
