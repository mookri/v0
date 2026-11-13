<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('jyotish_tool_registry', function (Blueprint $table): void {
            $table->string('tool_code', 16)->primary();
            $table->string('name', 128);
            $table->string('category', 64);
            $table->string('spec_id', 32);
            $table->string('ruleset_code', 64);
            $table->string('permission', 64);
            $table->string('verification_status', 32)->default('pending');
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('jyotish_places', function (Blueprint $table): void {
            $table->string('place_id', 26)->primary();
            $table->string('name_en', 128);
            $table->string('name_devanagari', 128)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('elevation_m')->default(0);
            $table->string('iana_timezone', 64)->default('Asia/Kolkata');
            $table->string('tzdb_version', 32)->default('2026a');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::connection('central')->create('jyotish_rulesets', function (Blueprint $table): void {
            $table->id();
            $table->string('ruleset_code', 64)->unique();
            $table->string('version', 32);
            $table->string('lineage', 64);
            $table->json('rules_json');
            $table->string('source_reference', 255);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('jyotish_generations', function (Blueprint $table): void {
            $table->id();
            $table->integer('generation_number');
            $table->string('rules_version', 32);
            $table->string('state', 32); // building, validated, active, retired
            $table->timestamp('promoted_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->json('metrics_json')->nullable();
            $table->timestamps();

            $table->index(['rules_version', 'state'], 'idx_jyotish_gen_state');
        });

        Schema::connection('central')->create('jyotish_ruleset_audit', function (Blueprint $table): void {
            $table->id();
            $table->string('ruleset_code', 64);
            $table->string('action', 64);
            $table->json('previous_state_json')->nullable();
            $table->json('new_state_json')->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ruleset_code', 'created_at'], 'idx_jyotish_audit_ruleset');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('jyotish_ruleset_audit');
        Schema::connection('central')->dropIfExists('jyotish_generations');
        Schema::connection('central')->dropIfExists('jyotish_rulesets');
        Schema::connection('central')->dropIfExists('jyotish_places');
        Schema::connection('central')->dropIfExists('jyotish_tool_registry');
    }
};

