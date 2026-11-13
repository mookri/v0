<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds on-read integrity state, timestamp, and upload quarantine status to
 * application_documents (SPEC §5, §14 / Task T-TR01-02 & T-TR01-03).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('application_documents', function (Blueprint $table): void {
            $table->string('integrity_state', 24)->default('unverified')->after('reason_code');
            $table->timestamp('integrity_last_checked_at')->nullable()->after('integrity_state');
            $table->string('quarantine_status', 24)->default('clean')->after('integrity_last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('application_documents', function (Blueprint $table): void {
            $table->dropColumn(['integrity_state', 'integrity_last_checked_at', 'quarantine_status']);
        });
    }
};
