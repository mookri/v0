<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Back-channel logout delivery outbox (SEC-004 / A-SEC-004-5, central):
 * one row per (session, RP) logout-token delivery attempt lifecycle —
 * created pending when the OP session ends, attempted inline
 * immediately, retried by auth:retry-backchannel-logout on the backoff
 * schedule until delivered or terminal (attempts >= cap, state
 * 'failed'). The logout token itself is NEVER stored: it is re-minted
 * per attempt through the signing custody (fresh iat/exp/jti every
 * try), so a stored row can never become a stale or leaked credential.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('oidc_backchannel_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('sid', 64);
            $table->string('client_id', 80);
            $table->text('logout_uri');
            // sub claim (external_id ?? id) resolved once at dispatch so
            // retries never re-query the user the session belonged to.
            $table->string('subject', 64);
            $table->string('state', 16)->default('pending'); // pending|delivered|failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('next_attempt_at');
            $table->text('last_error')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'next_attempt_at']);
            $table->index('sid');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('oidc_backchannel_deliveries');
    }
};
