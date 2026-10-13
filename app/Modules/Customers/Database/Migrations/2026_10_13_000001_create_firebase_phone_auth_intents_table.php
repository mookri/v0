<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firebase phone-login intents (central, deliberately scopeless — like
 * customer_otp_challenges, public auth routes run with no resolved
 * tenant context, so every read predicates tenant_id explicitly).
 *
 * The intent is the anti-replay hinge of the Firebase login flow: it
 * binds the E.164 number a client asked to sign in with to the ID token
 * that will be presented at exchange time (token phone must equal this
 * row's phone; the token's auth_time must post-date this row's
 * creation). It stores NO token, NO OTP, NO code hash — a single-use row
 * with a 10-minute TTL that exchange deletes the moment it consumes it,
 * so neither the intent nor the token behind it can satisfy a second
 * session request.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('firebase_phone_auth_intents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('purpose', 24)->default('login');
            $table->string('phone', 24);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['tenant_id', 'phone']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('firebase_phone_auth_intents');
    }
};
