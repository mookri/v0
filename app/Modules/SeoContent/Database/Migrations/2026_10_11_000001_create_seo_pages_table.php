<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO content pages — platform-global (central connection).
 * One row per page; body HTML lives in page_json, search_text is stripped for FULLTEXT.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('seo_pages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('slug', 190)->unique();
            $table->string('cluster', 64)->index();
            $table->boolean('noindex')->default(false);
            $table->string('title', 255);
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->date('last_updated')->nullable();
            $table->timestamp('automation_generated_at')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->longText('page_json'); // full page document (HTML body + structured fields)
            $table->longText('search_text')->nullable(); // HTML-stripped body for FULLTEXT
            $table->timestamps();

            $table->index(['cluster', 'noindex']);
        });

        // MySQL FULLTEXT on search_text, title, meta_title (driver-guarded)
        if (config('database.connections.central.driver') === 'mysql') {
            \DB::connection('central')->statement('
                ALTER TABLE `seo_pages`
                ADD FULLTEXT INDEX `ft_search_text_title_metatitle` (`search_text`, `title`, `meta_title`) WITH PARSER ngram
            ');
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('seo_pages');
    }
};
