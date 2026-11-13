<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('reco_bandit_stats', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('entity_type', 32);
            $table->string('entity_id', 36);
            $table->unsignedInteger('alpha')->default(1);
            $table->unsignedInteger('beta')->default(19);
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id'], 'uq_reco_bandit_stats');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('reco_bandit_stats');
    }
};
