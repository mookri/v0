<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add canonical region reference, source freshness timestamp, and multi-key set to search_documents.
 * Closes D-02 (freshness decoupling) and D-04 (canonical locality).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('search_documents', function (Blueprint $table): void {
            $table->string('region_id', 26)->nullable()->after('city')->index();
            $table->timestamp('source_updated_at')->nullable()->after('indexed_at')->index();
            $table->json('keys_json')->nullable()->after('card_json');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('search_documents', function (Blueprint $table): void {
            $table->dropIndex(['region_id']);
            $table->dropIndex(['source_updated_at']);
            $table->dropColumn(['region_id', 'source_updated_at', 'keys_json']);
        });
    }
};
