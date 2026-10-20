<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HMAC actor-token session store (M5 / TOKEN-001): every token minted by
 * ActorTokenIssuer::mint() persists its jti (the `s` claim, sess_<ulid>)
 * here so logout, rotation, revoke-others and suspension can kill a
 * bearer before its 24 h expiry. ActorTokenVerifier fails closed on a
 * missing or revoked row — a swept row is a dead token. Central: actor
 * identity is a platform (users) concern, not a tenant's.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('actor_sessions', function (Blueprint $table): void {
            $table->string('id', 48)->primary();
            $table->string('user_id', 64); // users.external_id (token `u` claim)
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('actor_sessions');
    }
};
