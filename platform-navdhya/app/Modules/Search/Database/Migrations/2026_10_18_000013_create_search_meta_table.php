<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * S59 — global search index state (version counter + corpus size).
 * Single row 'search_version' is incremented on every document/term write
 * so BFFs can detect staleness cheaply (HEAD / index-docs ETag).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_meta', function (Blueprint $table): void {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        DB::connection('central')->table('search_meta')->insert([
            'key' => 'search_version',
            'value' => '0',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_meta');
    }
};