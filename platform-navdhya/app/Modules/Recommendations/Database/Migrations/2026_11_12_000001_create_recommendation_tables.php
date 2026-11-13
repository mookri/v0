<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recommendation core schemas: impressions, interactions, item neighbors,
 * transitions, user interest profiles, and exclusions.
 * Part of Epic E-SR-07 (Wave 2, Gate G-REC-0).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('reco_impressions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('surface', 32)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->string('entity_type', 32);
            $table->string('entity_id', 36);
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('reason_code', 64)->index();
            $table->timestamp('impressed_at')->index();
            $table->timestamps();

            $table->index(['surface', 'impressed_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::connection('central')->create('reco_interactions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('reco_impression_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->string('entity_type', 32);
            $table->string('entity_id', 36);
            $table->string('interaction_type', 32);
            $table->unsignedInteger('dwell_ms')->default(0);
            $table->timestamp('interacted_at')->index();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        Schema::connection('central')->create('reco_item_neighbors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('source_type', 32);
            $table->string('source_id', 36);
            $table->string('neighbor_type', 32);
            $table->string('neighbor_id', 36);
            $table->decimal('sim_final', 4, 3)->default(0.000);
            $table->string('relation_kind', 24)->default('complementary');
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'neighbor_type', 'neighbor_id'], 'uq_reco_item_neighbors');
            $table->index(['source_type', 'source_id', 'sim_final'], 'idx_reco_neighbors_score');
        });

        Schema::connection('central')->create('reco_transitions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('source_id', 36)->index();
            $table->string('target_id', 36)->index();
            $table->unsignedInteger('count')->default(1);
            $table->decimal('p_estimate', 5, 4)->default(0.0000);
            $table->timestamps();

            $table->unique(['source_id', 'target_id'], 'uq_reco_transitions');
        });

        Schema::connection('central')->create('reco_user_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('tradition', 32)->nullable();
            $table->string('gotra', 64)->nullable();
            $table->string('preferred_region_id', 26)->nullable()->index();
            $table->string('preferred_language', 16)->default('en');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('reco_user_interests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('interest_kind', 32);
            $table->string('interest_key', 128);
            $table->decimal('weight', 4, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['user_id', 'interest_kind', 'interest_key'], 'uq_reco_user_interests');
        });

        Schema::connection('central')->create('reco_exclusions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('entity_type', 32);
            $table->string('entity_id', 36);
            $table->string('reason', 64);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'entity_type', 'entity_id'], 'idx_reco_user_exclusions');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('reco_exclusions');
        Schema::connection('central')->dropIfExists('reco_user_interests');
        Schema::connection('central')->dropIfExists('reco_user_profiles');
        Schema::connection('central')->dropIfExists('reco_transitions');
        Schema::connection('central')->dropIfExists('reco_item_neighbors');
        Schema::connection('central')->dropIfExists('reco_interactions');
        Schema::connection('central')->dropIfExists('reco_impressions');
    }
};

