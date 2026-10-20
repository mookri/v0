<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OAuth client profiles (SEC-002 §2.3, central): kernel extension of
 * Passport's oauth_clients with type, tenant binding, allowed grants /
 * scopes, audience, TTL overrides, and rate tier. The token-grant
 * middleware enforces allowed_grants per client; tenant binding feeds
 * token claims in P3b.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('oauth_client_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('client_id', 80)->unique();
            $table->string('client_type', 32);
            $table->string('tenant_id', 26)->nullable();
            $table->json('allowed_grants');
            $table->json('allowed_scopes');
            $table->string('audience', 190)->nullable();
            $table->unsignedInteger('access_ttl_seconds')->nullable();
            $table->unsignedInteger('refresh_ttl_seconds')->nullable();
            $table->boolean('require_pkce')->default(true);
            $table->string('rate_tier', 32)->default('standard');
            $table->timestamps();

            $table->index(['tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('oauth_client_profiles');
    }
};
