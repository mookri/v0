<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('jyotish_muhurat_candidates', function (Blueprint $table): void {
            $table->id();
            $table->string('candidate_id', 36)->unique();
            $table->string('occasion_code', 32)->index();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->decimal('score', 5, 2);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('timezone', 64);
            $table->json('reasons');
            $table->json('exclusions');
            $table->json('planetary_snapshot');
            $table->timestamps();

            $table->index(['occasion_code', 'starts_at', 'score'], 'idx_muhurat_search');
        });

        Schema::connection('central')->create('jyotish_dasha_intervals', function (Blueprint $table): void {
            $table->id();
            $table->string('birth_profile_id', 36)->index();
            $table->string('system', 32); // VIMSHOTTARI, ASHTOTTARI, YOGINI, CHARA, KALACHAKRA
            $table->integer('level'); // 1 = Maha, 2 = Antar, 3 = Pratyantar, 4 = Sookshma, 5 = Prana
            $table->string('lord', 16);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('parent_interval_id', 36)->nullable();
            $table->string('year_convention', 32)->default('365.25_SOLAR_DAYS');
            $table->timestamps();

            $table->index(['birth_profile_id', 'system', 'level', 'starts_at', 'ends_at'], 'idx_dasha_lookup');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('jyotish_dasha_intervals');
        Schema::connection('central')->dropIfExists('jyotish_muhurat_candidates');
    }
};

