<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('reco_user_profiles', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('reco_user_profiles', 'personalization_enabled')) {
                $table->boolean('personalization_enabled')->default(true)->after('user_id');
            }
        });

        Schema::connection('central')->table('reco_user_interests', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('reco_user_interests', 'source')) {
                $table->string('source', 16)->default('inferred')->after('weight');
            }
            if (! Schema::connection('central')->hasColumn('reco_user_interests', 'decay_half_life_days')) {
                $table->unsignedSmallInteger('decay_half_life_days')->default(30)->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('reco_user_interests', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('reco_user_interests', 'decay_half_life_days')) {
                $table->dropColumn('decay_half_life_days');
            }
            if (Schema::connection('central')->hasColumn('reco_user_interests', 'source')) {
                $table->dropColumn('source');
            }
        });

        Schema::connection('central')->table('reco_user_profiles', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('reco_user_profiles', 'personalization_enabled')) {
                $table->dropColumn('personalization_enabled');
            }
        });
    }
};
