<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S59 — search core v2: popularity + locale on documents.
 * These feed the custom scorer (bookings_30d, views_7d) and the
 * locale_match component. Defaults keep existing rows valid.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('search_documents', function (Blueprint $table): void {
            $table->unsignedInteger('bookings_30d')->default(0)->after('city');
            $table->unsignedInteger('views_7d')->default(0)->after('bookings_30d');
            $table->string('locale', 8)->default('en')->after('views_7d');
            $table->timestamp('popularity_updated_at')->nullable()->after('locale');
            $table->index(['locale']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('search_documents', function (Blueprint $table): void {
            $table->dropIndex(['locale']);
            $table->dropColumn(['bookings_30d', 'views_7d', 'locale', 'popularity_updated_at']);
        });
    }
};