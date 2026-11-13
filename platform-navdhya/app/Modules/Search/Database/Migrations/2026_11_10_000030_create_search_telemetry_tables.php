<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_query_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('trace_id', 36)->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('surface', 32)->default('web_search_page');
            $table->string('query_text_raw', 255);
            $table->string('query_text_norm', 255)->index();
            $table->string('script_family', 16)->default('latin');
            $table->string('region_code', 32)->nullable()->index();
            $table->string('plan_type', 32)->nullable();
            $table->json('plan_json')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->string('zero_result_reason', 32)->nullable();
            $table->unsignedInteger('generation')->nullable();
            $table->unsignedSmallInteger('stage_parse_ms')->default(0);
            $table->unsignedSmallInteger('stage_recall_ms')->default(0);
            $table->unsignedSmallInteger('stage_rank_ms')->default(0);
            $table->unsignedSmallInteger('stage_total_ms')->default(0);
            $table->timestamp('searched_at')->useCurrent()->index();

            $table->index(['searched_at', 'zero_result_reason']);
            $table->index(['query_text_norm', 'searched_at']);
        });

        Schema::connection('central')->create('search_impressions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('trace_id', 36)->index();
            $table->string('entity_type', 32)->index();
            $table->string('entity_id', 36)->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('page_offset')->default(0);
            $table->decimal('score', 8, 4)->nullable();
            $table->unsignedInteger('dwell_ms')->default(0);
            $table->decimal('viewport_ratio', 4, 2)->default(0.50);
            $table->timestamp('impressed_at')->useCurrent()->index();

            $table->index(['trace_id', 'position']);
            $table->index(['entity_type', 'entity_id', 'impressed_at']);
        });

        Schema::connection('central')->create('search_selections', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('trace_id', 36)->index();
            $table->string('entity_type', 32)->index();
            $table->string('entity_id', 36)->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('action', 32)->default('click');
            $table->unsignedInteger('dwell_before_click_ms')->nullable();
            $table->timestamp('selected_at')->useCurrent()->index();

            $table->index(['entity_type', 'entity_id', 'selected_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_selections');
        Schema::connection('central')->dropIfExists('search_impressions');
        Schema::connection('central')->dropIfExists('search_query_events');
    }
};
