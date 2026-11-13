<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('calendar_computation_log', function (Blueprint $table): void {
            $table->unsignedInteger('ephemeris_calls')->default(1)->after('cache_hit');
            $table->unsignedInteger('root_iterations')->default(0)->after('ephemeris_calls');
            $table->unsignedInteger('ruleset_evals')->default(1)->after('root_iterations');
            $table->unsignedInteger('db_queries')->default(0)->after('ruleset_evals');
            $table->unsignedBigInteger('peak_mem_bytes')->default(0)->after('db_queries');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('calendar_computation_log', function (Blueprint $table): void {
            $table->dropColumn([
                'ephemeris_calls',
                'root_iterations',
                'ruleset_evals',
                'db_queries',
                'peak_mem_bytes',
            ]);
        });
    }
};

