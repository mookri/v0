<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verification Fabric → MSG91 widget OTP (schema half). Changes, all
 * additive or widening — existing rows and every current query keep
 * working:
 *
 * 1. `id` CHAR(26) → CHAR(32): step-up rows wrote `chl_<ulid>`
 *    (30 chars) and MySQL strict mode rejects the overflow — the root
 *    cause of the MySQL-only MFA/step-up test cluster (sqlite never
 *    enforced the length). PK and NOT NULL are preserved: MySQL MODIFY
 *    leaves indexes untouched, SQLite's change rebuild re-applies the
 *    introspected primary key.
 * 2. `code_hash` becomes nullable: the MSG91 widget generates and
 *    judges codes in the browser — those rows carry `provider_ref`
 *    instead. Local sends (if ever re-adopted) keep hashing as before.
 * 3. Provider provenance columns: `provider` (which port issued the
 *    challenge; default `msg91_widget`), `provider_ref` (opaque remote
 *    handle), `status` (send FSM — `sent` at insert, `token_verified`
 *    on an MFA hold), `decision` (replayable routing decision record).
 * 4. Resend accounting for the compounding schedule: `resend_count`
 *    (resends spent of max 3) and `last_sent_at` (drives the 60s →
 *    180s → 540s delays and the fresh-challenge back-off).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('customer_otp_challenges', function (Blueprint $table): void {
            $table->char('id', 32)->change();
            $table->string('code_hash', 64)->nullable()->change();
        });

        Schema::connection('central')->table('customer_otp_challenges', function (Blueprint $table): void {
            $table->string('provider', 24)->default('msg91_widget')->after('code_hash');
            $table->text('provider_ref')->nullable()->after('provider');
            $table->string('status', 16)->default('sent')->after('provider_ref');
            $table->json('decision')->nullable()->after('status');
            $table->unsignedInteger('resend_count')->default(0)->after('decision');
            $table->timestamp('last_sent_at')->nullable()->after('resend_count');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('customer_otp_challenges', function (Blueprint $table): void {
            $table->dropColumn(['provider', 'provider_ref', 'status', 'decision', 'resend_count', 'last_sent_at']);

            // `id`/`code_hash` are deliberately not narrowed back: live
            // rows may carry 30-char step-up ids or null hashes, and a
            // narrowing ALTER would fail on that data.
        });
    }
};
