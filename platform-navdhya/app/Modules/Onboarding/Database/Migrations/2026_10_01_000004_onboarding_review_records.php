<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Review audit trail (stage 7 port): append-only decision records with
 * reviewer, reason and countersignature. State lives on the application;
 * this table is the evidence.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('review_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('application_id', 26);
            $table->string('reviewer_id', 64);
            $table->string('decision', 24);
            $table->string('reason_code', 64)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'application_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('review_records');
    }
};
