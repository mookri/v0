<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S60 — forensic tracing: store trace_id + search_version on each
 * analytics row so a bad historical result set can be replayed.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('search_queries', function (Blueprint $table): void {
            $table->string('trace_id', 64)->nullable()->index()->after('searched_at');
            $table->unsignedBigInteger('search_version')->nullable()->after('trace_id');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('search_queries', function (Blueprint $table): void {
            $table->dropColumn(['trace_id', 'search_version']);
        });
    }
};
