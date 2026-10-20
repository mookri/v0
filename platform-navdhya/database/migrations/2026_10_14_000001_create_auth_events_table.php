<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Federation audit events (ADR-0020 D5): append-only, hash-chained,
 * correlated, non-secret. Central connection — auth decisions are
 * platform identity, not tenant data. Identifiers are pseudonymous
 * refs (subject/session HMAC-truncated); tokens, OTPs, passwords and
 * keys are never columns here. `prev_hash`/`hash` form one global
 * chain ordered by ULID id; `auth:verify-chain` re-walks it.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('auth_events', function (Blueprint $table): void {
            $table->string('id', 48)->primary(); // ULID
            $table->string('event_type', 96);
            $table->timestamp('occurred_at');
            $table->string('request_id', 64)->nullable();
            $table->string('trace_id', 64)->nullable();
            $table->string('actor_client_id', 64)->nullable();
            $table->string('subject_ref', 80)->nullable(); // HMAC-pseudonymous
            $table->string('tenant_ref', 64)->nullable();
            $table->string('session_ref', 80)->nullable(); // HMAC-pseudonymous
            $table->string('grant_type', 64)->nullable();
            $table->string('source_audience', 64)->nullable();
            $table->string('target_audience', 64)->nullable();
            $table->json('requested_scopes')->nullable();
            $table->json('granted_scopes')->nullable();
            $table->json('authentication_methods')->nullable();
            $table->string('authentication_assurance', 32)->nullable();
            $table->string('risk_tier', 16)->nullable();
            $table->string('policy_version', 32)->nullable();
            $table->string('decision', 16); // allow | deny | error
            $table->string('reason_code', 64)->nullable();
            $table->string('signing_key_id', 64)->nullable();
            $table->string('token_jti_ref', 80)->nullable(); // HMAC ref, never the token
            $table->string('prev_hash', 64)->nullable();
            $table->string('hash', 64);

            $table->index('event_type');
            $table->index('subject_ref');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('auth_events');
    }
};
