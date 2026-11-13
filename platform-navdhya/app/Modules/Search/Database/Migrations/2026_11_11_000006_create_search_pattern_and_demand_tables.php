<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materialized patterns, locality supply-demand, and typed sessions telemetry.
 * Part of Epic E-SR-06 (RC2 Gate).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_query_patterns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('pattern_type', 32)->index();
            $table->string('pattern_key', 190)->index();
            $table->unsignedInteger('sample_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('empty_count')->default(0);
            $table->json('metrics_json')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();

            $table->index(['pattern_type', 'sample_count']);
        });

        Schema::connection('central')->create('search_locality_demand', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('region_id', 26)->nullable()->index();
            $table->string('region_name', 64);
            $table->string('entity_type', 32)->nullable();
            $table->unsignedInteger('total_searches')->default(0);
            $table->unsignedInteger('empty_searches')->default(0);
            $table->decimal('unserved_rate', 5, 2)->default(0.00);
            $table->date('period_date')->index();
            $table->timestamps();

            $table->unique(['region_name', 'entity_type', 'period_date']);
        });

        Schema::connection('central')->create('search_typed_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('session_id', 64)->index();
            $table->string('trace_id', 36)->nullable();
            $table->string('query_text', 190);
            $table->unsignedSmallInteger('keystroke_count')->default(0);
            $table->unsignedSmallInteger('backspace_count')->default(0);
            $table->boolean('ime_composition')->default(false);
            $table->unsignedInteger('time_to_first_impression_ms')->default(0);
            $table->unsignedInteger('total_typing_ms')->default(0);
            $table->timestamp('settled_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_typed_sessions');
        Schema::connection('central')->dropIfExists('search_locality_demand');
        Schema::connection('central')->dropIfExists('search_query_patterns');
    }
};

