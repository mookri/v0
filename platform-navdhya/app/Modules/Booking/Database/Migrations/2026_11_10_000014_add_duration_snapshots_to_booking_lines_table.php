<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $conn = 'tenant_shared';
        if (Schema::connection($conn)->hasTable('booking_lines')) {
            Schema::connection($conn)->table('booking_lines', function (Blueprint $table) use ($conn) {
                if (! Schema::connection($conn)->hasColumn('booking_lines', 'duration_rule_snapshot')) {
                    $table->json('duration_rule_snapshot')->nullable()->after('server_duration_minutes');
                }
                if (! Schema::connection($conn)->hasColumn('booking_lines', 'parent_line_id')) {
                    $table->string('parent_line_id', 26)->nullable()->after('revision_id');
                }
                if (! Schema::connection($conn)->hasColumn('booking_lines', 'price_cents')) {
                    $table->unsignedInteger('price_cents')->default(0)->after('quantity');
                }
            });
        }
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        if (Schema::connection($conn)->hasTable('booking_lines')) {
            Schema::connection($conn)->table('booking_lines', function (Blueprint $table) use ($conn) {
                if (Schema::connection($conn)->hasColumn('booking_lines', 'duration_rule_snapshot')) {
                    $table->dropColumn('duration_rule_snapshot');
                }
                if (Schema::connection($conn)->hasColumn('booking_lines', 'parent_line_id')) {
                    $table->dropColumn('parent_line_id');
                }
                if (Schema::connection($conn)->hasColumn('booking_lines', 'price_cents')) {
                    $table->dropColumn('price_cents');
                }
            });
        }
    }
};
