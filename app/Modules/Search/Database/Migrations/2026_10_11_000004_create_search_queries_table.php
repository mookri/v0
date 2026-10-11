<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Search analytics — sanitized queries only (no identities/tokens).
 * Batched writes from BFF; read path for trending + zero-result feed.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_queries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('query_hash', 64)->index(); // SHA-256 of normalized query
            $table->string('query_text', 200); // original query (sanitized, no PII)
            $table->string('entity_type', 32)->nullable()->index(); // filtered type
            $table->string('city', 100)->nullable()->index(); // filtered city
            $table->unsignedInteger('result_count')->default(0);
            $table->boolean('zero_results')->default(false)->index();
            $table->timestamp('searched_at')->useCurrent()->index();
            $table->index(['searched_at', 'zero_results']);
            $table->index(['query_hash', 'searched_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_queries');
    }
};
