<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Split search index into durable vocabulary dictionary, posting lists, and deletion-neighborhood index.
 * Closes D-06 (eliminating unindexed levenshtein scans) and D-07 (durable dictionary & postings).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_dictionary', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->nullable()->index();
            $table->string('locale', 8)->default('en');
            $table->string('scheme', 16)->default('latin_fold');
            $table->string('term_key', 190);
            $table->unsignedSmallInteger('key_len')->default(0);
            $table->unsignedInteger('doc_freq')->default(0);
            $table->unsignedInteger('popularity')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->string('first_document_id', 26)->nullable();
            $table->unsignedInteger('generation')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'locale', 'scheme', 'term_key']);
            $table->index(['scheme', 'term_key']);
            $table->index(['term_key', 'popularity']);
        });

        Schema::connection('central')->create('search_postings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('dictionary_id');
            $table->ulid('search_document_id');
            $table->string('field', 16)->default('title');
            $table->decimal('field_weight', 4, 2)->default(1.00);
            $table->unsignedSmallInteger('tf')->default(1);
            $table->json('positions_json')->nullable();
            $table->timestamps();

            $table->foreign('dictionary_id')->references('id')->on('search_dictionary')->onDelete('cascade');
            $table->foreign('search_document_id')->references('id')->on('search_documents')->onDelete('cascade');
            $table->unique(['dictionary_id', 'search_document_id', 'field']);
            $table->index(['search_document_id', 'field']);
        });

        Schema::connection('central')->create('search_deletes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('delete_form', 190);
            $table->string('original_key', 190);
            $table->string('scheme', 16)->default('latin_fold');
            $table->unsignedTinyInteger('edits')->default(1);
            $table->timestamps();

            $table->unique(['delete_form', 'original_key', 'scheme']);
            $table->index(['delete_form', 'scheme']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_deletes');
        Schema::connection('central')->dropIfExists('search_postings');
        Schema::connection('central')->dropIfExists('search_dictionary');
    }
};
