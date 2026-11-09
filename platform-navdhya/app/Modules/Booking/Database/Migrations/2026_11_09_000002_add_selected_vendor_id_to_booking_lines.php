<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $conn = 'tenant_shared';
        if (Schema::connection($conn)->hasTable('booking_lines')) {
            Schema::connection($conn)->table('booking_lines', function (Blueprint $table): void {
                if (! Schema::connection('tenant_shared')->hasColumn('booking_lines', 'selected_vendor_id')) {
                    $table->string('selected_vendor_id', 26)->nullable()->after('selected_temple_id');
                    $table->index(['tenant_id', 'selected_vendor_id']);
                }
            });
        }
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        if (Schema::connection($conn)->hasTable('booking_lines') && Schema::connection($conn)->hasColumn('booking_lines', 'selected_vendor_id')) {
            Schema::connection($conn)->table('booking_lines', function (Blueprint $table): void {
                $table->dropIndex(['tenant_id', 'selected_vendor_id']);
                $table->dropColumn('selected_vendor_id');
            });
        }
    }
};
