<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transactional search index outbox (tenant_shared).
 * Closes D-09 (decoupling observer writes from central transactions).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('search_index_outbox', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('entity_type', 32)->index();
            $table->string('entity_id', 36)->index();
            $table->unsignedInteger('revision')->default(1);
            $table->string('operation', 16)->default('UPSERT');
            $table->string('status', 16)->default('PENDING')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('payload_json')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'lease_expires_at']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('search_index_outbox');
    }
};
