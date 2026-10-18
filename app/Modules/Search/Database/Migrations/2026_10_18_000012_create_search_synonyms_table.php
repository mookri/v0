<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S59 — synonym graph (1 hop, versioned data not code).
 * term -> synonym with an optional weight multiplier. Locale-scoped so
 * Hindi/English synonym sets stay separate.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_synonyms', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('term', 120);
            $table->string('synonym', 120);
            $table->string('locale', 8)->default('en');
            $table->decimal('weight', 6, 4)->default(1.0000);
            $table->timestamps();

            $table->unique(['term', 'synonym', 'locale']);
            $table->index(['term', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_synonyms');
    }
};