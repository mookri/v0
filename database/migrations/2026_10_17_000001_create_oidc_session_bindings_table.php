<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OIDC session<->client bindings (SEC-004 / A-SEC-004-5, central): an
 * openid authorization records (sid, client, user) so that ending the
 * OP session can enumerate exactly the relying parties owed a
 * back-channel logout token (OIDC Back-Channel Logout 1.0). One row per
 * (sid, client): repeat authorizations from the same browser session
 * touch the same binding. Rows live as long as the refresh lineage
 * they serve (30 d, Passport's refresh TTL) and are marked notified_at
 * when their logout token has been dispatched (or the client has no
 * endpoint registered — delivery is the delivery table's concern, this
 * table is the enumeration).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('oidc_session_bindings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('sid', 64);
            $table->string('client_id', 80);
            $table->unsignedBigInteger('user_id');
            $table->dateTime('expires_at');
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['sid', 'client_id']);
            $table->index(['sid', 'notified_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('oidc_session_bindings');
    }
};
