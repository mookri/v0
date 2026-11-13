<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Communication templates registry (E-CM-04 / Task T-CM04-02).
 * Stores Meta WhatsApp / notification templates with categories and approval status.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('communication_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('category', 32); // UTILITY | AUTHENTICATION | MARKETING | SERVICE
            $table->string('meta_template_id', 64)->nullable();
            $table->string('approval_status', 32)->default('APPROVED'); // APPROVED | PENDING | REJECTED | PAUSED
            $table->string('language', 12)->default('en_US');
            $table->text('content');
            $table->json('variables')->nullable();
            $table->timestamps();

            $table->index(['category', 'approval_status'], 'comm_tmpl_cat_status_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('communication_templates');
    }
};

