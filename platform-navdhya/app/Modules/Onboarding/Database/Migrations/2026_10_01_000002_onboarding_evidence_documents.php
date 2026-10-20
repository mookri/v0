<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding evidence + documents (stage parity with the Next.js BFF):
 * draft evidence as versioned JSON on the application; document metadata
 * in its own tenant-scoped table (bytes live in object storage, never in
 * the database).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->table('partner_applications', function (Blueprint $table): void {
            $table->json('evidence')->nullable()->after('market');
        });

        Schema::connection('tenant_shared')->create('application_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('application_id', 26);
            $table->string('kind', 32);
            $table->string('status', 24)->default('PENDING');
            $table->string('object_key');
            $table->string('sha256', 64)->nullable();
            $table->string('reason_code', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'application_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('application_documents');
        Schema::connection('tenant_shared')->table('partner_applications', function (Blueprint $table): void {
            $table->dropColumn('evidence');
        });
    }
};
