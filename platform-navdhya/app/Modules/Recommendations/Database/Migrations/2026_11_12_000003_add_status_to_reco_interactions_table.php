<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('reco_interactions', function (Blueprint $table): void {
            if (!Schema::connection('central')->hasColumn('reco_interactions', 'status')) {
                $table->string('status', 24)->default('verified')->after('interaction_type');
            }
        });
    }


    public function down(): void
    {
        Schema::connection('central')->table('reco_interactions', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('reco_interactions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
