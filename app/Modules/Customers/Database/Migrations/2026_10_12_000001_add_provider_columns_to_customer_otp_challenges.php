<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verification Fabric S46 (schema half). Three changes, all additive or
 * widening — existing rows and every current query keep working:
 *
 * 1. `id` CHAR(26) → CHAR(32): StepUpOtpService writes `chl_<ulid>`
 *    (30 chars) into this table and MySQL strict mode rejects the
 *    overflow — the root cause of the MySQL-only MFA/step-up test
 *    cluster (sqlite never enforced the length). PK and NOT NULL are
 *    preserved: MySQL MODIFY leaves indexes untouched, SQLite's change
 *    rebuild re-applies the introspected primary key.
 * 2. `code_hash` becomes nullable: remote phone providers (Firebase
 *    Identity Toolkit, S47+) generate codes we never see — those rows
 *    carry `provider_ref` instead. Local sends keep hashing as before.
 * 3. Provider provenance columns: `provider` (which port issued the
 *    challenge; backfilled `local`), `provider_ref` (opaque remote
 *    handle), `status` (send FSM — local rows are `sent` at insert),
 *    `decision` (replayable routing decision record, S48+).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('customer_otp_challenges', function (Blueprint $table): void {
            $table->char('id', 32)->change();
            $table->string('code_hash', 64)->nullable()->change();
        });

        Schema::connection('central')->table('customer_otp_challenges', function (Blueprint $table): void {
            $table->string('provider', 24)->default('local')->after('code_hash');
            $table->text('provider_ref')->nullable()->after('provider');
            $table->string('status', 16)->default('sent')->after('provider_ref');
            $table->json('decision')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('customer_otp_challenges', function (Blueprint $table): void {
            $table->dropColumn(['provider', 'provider_ref', 'status', 'decision']);

            // `id`/`code_hash` are deliberately not narrowed back: live
            // rows may carry 30-char step-up ids or (post-S47) null
            // hashes, and a narrowing ALTER would fail on that data.
        });
    }
};
