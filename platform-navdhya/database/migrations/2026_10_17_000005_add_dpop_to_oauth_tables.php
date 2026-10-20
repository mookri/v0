<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sender-constrained access tokens (A-SEC-004-9, RFC 9449): the JWK
 * thumbprint (jkt) a DPoP-bound mint proved travels on the access-token
 * ROW beside its JWT `cnf.dpop_jkt` claim — one source (the entity
 * stamp) for both halves, exactly like sid — so the resource server can
 * demand the key without decoding anything it cannot verify. The
 * `require_dpop` profile flag makes the binding a per-client mandate
 * (auth-code + refresh seams; machine grants are denied outright for a
 * mandated client until DPoP binding lands for them — fail closed,
 * never silently exempt).
 */
return new class () extends Migration {
    public function up(): void
    {
        // Guarded: a partially-applied run (e.g. an interrupted e2e
        // provision) must be able to complete and be recorded rather
        // than dying on a duplicate column forever.
        Schema::connection('central')->table('oauth_access_tokens', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('oauth_access_tokens', 'dpop_jkt')) {
                $table->string('dpop_jkt', 128)->nullable()->after('sid');
            }
        });

        Schema::connection('central')->table('oauth_client_profiles', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('oauth_client_profiles', 'require_dpop')) {
                $table->boolean('require_dpop')->default(false)->after('require_par');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('oauth_access_tokens', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('oauth_access_tokens', 'dpop_jkt')) {
                $table->dropColumn('dpop_jkt');
            }
        });

        Schema::connection('central')->table('oauth_client_profiles', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('oauth_client_profiles', 'require_dpop')) {
                $table->dropColumn('require_dpop');
            }
        });
    }
};
