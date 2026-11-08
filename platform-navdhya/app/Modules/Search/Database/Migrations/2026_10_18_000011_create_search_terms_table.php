<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S59 — per-term postings index that backs suggestion auto-complete.
 * One row per (normalized term, document) so prefix queries can join back
 * to search_documents and rank by weight. Nullable document_id allows
 * non-document suggestions (popular queries, categories, cities) in S64.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_terms', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('term', 120);                 // raw-ish token as stored
            $table->string('normalized', 120)->index();    // lowercased, folded token
            $table->string('kind', 32)->default('entity_title'); // entity_title|category|city|popular_query|synonym
            $table->decimal('weight', 8, 4)->default(1.0000);
            $table->ulid('search_document_id')->nullable()->index();
            $table->string('title', 255)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('tenant_id', 26)->nullable()->index();
            $table->string('locale', 8)->default('en');
            $table->timestamps();

            $table->index(['normalized', 'weight']);
            $table->index(['kind', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_terms');
    }
};
