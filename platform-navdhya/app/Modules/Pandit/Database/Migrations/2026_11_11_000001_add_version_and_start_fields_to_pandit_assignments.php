<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('pandit_assignments', function (Blueprint $table): void {
            $table->unsignedBigInteger('version')->default(1)->after('compensation_minor');
            $table->timestamp('service_started_at')->nullable()->after('confirmed_at');
            $table->json('checklist_json')->nullable()->after('decline_reason');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('pandit_assignments', function (Blueprint $table): void {
            $table->dropColumn(['version', 'service_started_at', 'checklist_json']);
        });
    }
};

