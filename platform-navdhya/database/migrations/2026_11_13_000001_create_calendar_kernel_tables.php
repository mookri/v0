<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('calendar_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('profile_code', 64)->unique();
            $table->string('ephemeris_version', 32);
            $table->string('ayanamsa_code', 32);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::connection('central')->create('calendar_places', function (Blueprint $table): void {
            $table->string('region_id', 26)->primary();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('elevation_m')->default(0);
            $table->string('iana_timezone', 64)->default('Asia/Kolkata');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::connection('central')->create('calendar_generations', function (Blueprint $table): void {
            $table->id();
            $table->string('profile_code', 64);
            $table->integer('scope_year');
            $table->string('state', 32); // building, validated, active, retired
            $table->json('metrics_json')->nullable();
            $table->timestamps();

            $table->index(['profile_code', 'scope_year', 'state'], 'idx_cal_gen_lookup');
        });

        Schema::connection('central')->create('calendar_panchang_days', function (Blueprint $table): void {
            $table->id();
            $table->string('place_id', 26);
            $table->string('profile_code', 64);
            $table->date('local_date');
            $table->bigInteger('sunrise_at_us');
            $table->bigInteger('sunset_at_us')->nullable();
            $table->bigInteger('solar_noon_us')->nullable();
            $table->string('masa_amanta', 32);
            $table->string('masa_purnimanta', 32);
            $table->boolean('is_adhika')->default(false);
            $table->integer('generation');
            $table->timestamps();

            $table->unique(['place_id', 'profile_code', 'local_date', 'generation'], 'uq_cal_panchang_days');
        });

        Schema::connection('central')->create('calendar_astronomical_events', function (Blueprint $table): void {
            $table->id();
            $table->string('place_id', 26);
            $table->string('profile_code', 64);
            $table->string('event_type', 32);
            $table->integer('index_in_series');
            $table->bigInteger('starts_at_us');
            $table->bigInteger('ends_at_us');
            $table->integer('generation');
            $table->timestamps();

            $table->index(['place_id', 'profile_code', 'event_type', 'starts_at_us'], 'idx_cal_astron_events');
        });

        Schema::connection('central')->create('calendar_computation_log', function (Blueprint $table): void {
            $table->id();
            $table->string('input_hash', 64)->index();
            $table->string('profile_code', 64);
            $table->decimal('execution_ms', 8, 2);
            $table->boolean('cache_hit')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('calendar_computation_log');
        Schema::connection('central')->dropIfExists('calendar_astronomical_events');
        Schema::connection('central')->dropIfExists('calendar_panchang_days');
        Schema::connection('central')->dropIfExists('calendar_generations');
        Schema::connection('central')->dropIfExists('calendar_places');
        Schema::connection('central')->dropIfExists('calendar_profiles');
    }
};

