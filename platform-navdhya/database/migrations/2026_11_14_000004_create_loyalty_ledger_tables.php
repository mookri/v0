<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Loyalty accounts
        Schema::connection('central')->create('loyalty_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('account_id', 36)->unique();
            $table->string('user_id', 36)->index();
            $table->string('tier_code', 32)->default('SADHAK'); // SADHAK, BHAKTA, SEVAK, ACHARYA
            $table->bigInteger('points_balance')->default(0);
            $table->bigInteger('lifetime_points_accrued')->default(0);
            $table->timestamps();
        });

        // 2. Loyalty append-only ledger with cryptographic hash chaining
        Schema::connection('central')->create('loyalty_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_id', 36)->unique();
            $table->string('account_id', 36)->index();
            $table->string('event_type', 64); // jyotish.chart.completed, puja.booking.completed, etc.
            $table->bigInteger('points_delta');
            $table->string('previous_hash', 64);
            $table->string('entry_hash', 64);
            $table->json('metadata');
            $table->timestamps();

            $table->index(['account_id', 'created_at'], 'idx_loyalty_account_entries');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('loyalty_entries');
        Schema::connection('central')->dropIfExists('loyalty_accounts');
    }
};

