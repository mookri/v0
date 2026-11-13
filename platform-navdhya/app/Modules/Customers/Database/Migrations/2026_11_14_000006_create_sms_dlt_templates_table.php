<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telecom TRAI DLT template registry (E-CM-05 / Task T-CM05-02).
 * Maps logical notification template codes to telecom registered Principal Entity (PE) ID,
 * sender header, and approved DLT template IDs.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('sms_dlt_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('template_code', 64)->unique();
            $table->string('pe_id', 32);
            $table->string('header', 16);
            $table->string('dlt_template_id', 32);
            $table->boolean('unicode_flag')->default(false);
            $table->text('content_template');
            $table->string('approval_status', 32)->default('APPROVED'); // APPROVED | PENDING | REJECTED
            $table->timestamps();

            $table->index(['approval_status', 'template_code'], 'sms_dlt_status_code_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('sms_dlt_templates');
    }
};

