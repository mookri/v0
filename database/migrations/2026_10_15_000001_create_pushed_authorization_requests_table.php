<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pushed authorization requests (RFC 9126, central): the storage behind
 * POST /api/oauth/par. A request_uri is a single-use, short-TTL
 * capability naming one pre-validated authorization request; only its
 * SHA-256 hash is stored (the raw urn is the bearer capability — a DB
 * read must never yield a redeemable request_uri). Consumption is an
 * atomic used_at claim (UPDATE … WHERE used_at IS NULL) so a replayed
 * request_uri can never race two authorization flows.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('pushed_authorization_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('request_uri_hash', 64);
            $table->string('client_id', 80);
            $table->json('params');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Resolution looks the capability up by its hash only.
            $table->unique('request_uri_hash');
            $table->index(['client_id']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('pushed_authorization_requests');
    }
};
