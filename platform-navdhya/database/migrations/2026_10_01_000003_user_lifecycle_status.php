<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User lifecycle status (central identity): active/suspended/deactivated.
 * Suspended users fail authentication everywhere; deactivated is terminal
 * (offboarding) and retains the row for audit. Additive column with a
 * safe default — no backfill needed (absence already meant active).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->string('status', 24)->default('active')->after('remember_token');
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
