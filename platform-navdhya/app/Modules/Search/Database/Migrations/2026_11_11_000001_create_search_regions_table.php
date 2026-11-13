<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_regions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('parent_id', 26)->nullable()->index();
            $table->string('kind', 16)->default('city'); // city, district, state, country
            $table->string('name_en', 100);
            $table->string('name_dev', 100)->nullable();
            $table->json('name_alt_json')->nullable();
            $table->string('geohash6', 6)->nullable()->index();
            $table->integer('lat_milli')->nullable();
            $table->integer('lng_milli')->nullable();
            $table->string('population_tier', 16)->default('TIER_2');
            $table->boolean('is_serviceable')->default(true)->index();
            $table->timestamps();

            $table->unique(['kind', 'name_en']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_regions');
    }
};
