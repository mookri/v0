<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-client PAR mandate (RFC 9126 §2.2, RFC 9126 metadata note): N2
 * clients (dashboard/admin/sensitive-partner/delegated-API) require a
 * pushed authorization request before the authorize endpoint will serve
 * them; N1 browser BFFs may opt in but are never globally mandated —
 * discovery deliberately does NOT advertise
 * require_pushed_request_parameters.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('oauth_client_profiles', function (Blueprint $table): void {
            $table->boolean('require_par')->default(false)->after('require_pkce');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('oauth_client_profiles', function (Blueprint $table): void {
            $table->dropColumn('require_par');
        });
    }
};
