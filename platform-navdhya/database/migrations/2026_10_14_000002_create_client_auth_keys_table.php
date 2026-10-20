<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client authentication public keys (ADR-0020 D3, central): the public
 * half of each per-client private_key_jwt key pair, registered against
 * that client only. One row per (client, kid); revocation is a
 * timestamp so a compromised key revokes without losing the forensic
 * record. Private halves never leave the client — nothing in this
 * table can mint, only verify.
 */
return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('central')->create('client_auth_keys', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('client_id', 80);
            $table->string('kid', 64);
            $table->text('public_key');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // Verification looks keys up by (client, kid) and never
            // wider: one client's key can't serve another's request.
            $table->unique(['client_id', 'kid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('client_auth_keys');
    }
};
