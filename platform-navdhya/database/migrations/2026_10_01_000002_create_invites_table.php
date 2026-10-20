<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track-scoped invites (central): token-hash only (plaintext shown once),
 * single-use lifecycle, expiry, per-track seeding on accept.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('invites', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('track', 32);
            $table->json('roles')->nullable();
            $table->string('identifier', 190);
            $table->string('token_hash', 64)->unique();
            $table->string('state', 24)->default('ISSUED');
            $table->timestamp('expires_at');
            $table->string('accepted_by', 64)->nullable();
            $table->string('created_by', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('invites');
    }
};
