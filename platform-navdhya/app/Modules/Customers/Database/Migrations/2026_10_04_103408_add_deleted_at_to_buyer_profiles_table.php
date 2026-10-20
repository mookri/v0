<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete support for buyer_profiles (account deletion).
 * Adds deleted_at timestamp + index for filtering.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('buyer_profiles', function (Blueprint $table): void {
            $table->timestamp('deleted_at')->nullable()->after('status');
            $table->index(['tenant_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('buyer_profiles', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'deleted_at']);
            $table->dropColumn('deleted_at');
        });
    }
};
