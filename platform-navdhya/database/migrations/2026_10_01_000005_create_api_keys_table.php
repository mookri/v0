<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Machine API keys (central): prefix-identifiable, SHA-256 stored secrets
 * (plaintext shown once), tenant binding, scopes, rate tier, expiry and
 * revocation. Rotation overlaps via dual-active rows (never secret edits).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('api_keys', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('name', 120);
            $table->string('prefix', 24)->unique();
            $table->string('secret_hash', 64)->unique();
            $table->json('scopes')->nullable();
            $table->string('rate_tier', 31)->default('standard');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('created_by', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('api_keys');
    }
};
