<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO clusters — platform-global metadata.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('seo_clusters', function (Blueprint $table): void {
            $table->string('cluster', 64)->primary();
            $table->string('label', 128);
            $table->string('base_path', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('seo_clusters');
    }
};
