<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('application_documents', function (Blueprint $table): void {
            $table->string('rejection_reason_code', 48)->nullable()->after('quarantine_status');
            $table->string('reviewed_by', 64)->nullable()->after('rejection_reason_code');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('expires_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('application_documents', function (Blueprint $table): void {
            $table->dropColumn(['rejection_reason_code', 'reviewed_by', 'reviewed_at', 'expires_at']);
        });
    }
};
