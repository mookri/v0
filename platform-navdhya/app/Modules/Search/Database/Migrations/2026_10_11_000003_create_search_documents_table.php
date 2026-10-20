<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Federated search index of record (central connection).
 * Null tenant_id = platform-global content (SEO pages).
 * Tenant-scoped entities (temples, pandits, puja services) carry their tenant_id.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('search_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('entity_type', 32)->index(); // seo_page | temple | pandit | puja_service
            $table->string('entity_id', 36); // ULID
            $table->string('tenant_id', 26)->nullable()->index(); // null = platform-global
            $table->string('slug', 190)->index();
            $table->string('url_path', 255);
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->longText('body')->nullable(); // stripped text for FULLTEXT
            $table->string('image_url', 500)->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->longText('card_json')->nullable(); // structured card payload (rating, price, badges, etc.)
            $table->string('status', 24)->default('ACTIVE'); // ACTIVE, DRAFT, INACTIVE, etc.
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id']);
            $table->index(['tenant_id', 'entity_type', 'status']);
            $table->index(['tenant_id', 'city', 'entity_type']);
        });

        // MySQL FULLTEXT with ngram parser (driver-guarded)
        if (config('database.connections.central.driver') === 'mysql') {
            DB::connection('central')->statement('
                ALTER TABLE `search_documents`
                ADD FULLTEXT INDEX `ft_title_subtitle_body` (`title`, `subtitle`, `body`) WITH PARSER ngram
            ');
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('search_documents');
    }
};
