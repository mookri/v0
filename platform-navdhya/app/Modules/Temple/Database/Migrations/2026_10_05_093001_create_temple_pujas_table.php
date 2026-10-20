<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temple pujas (tenant_shared): puja assignments for temples with overrides.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('temple_pujas', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('temple_id');
            $table->ulid('puja_id');
            $table->unsignedBigInteger('price_override_minor')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->json('schedule_json')->nullable(); // day_of_week -> [{start, end, capacity}]
            $table->string('status', 24)->default('ACTIVE'); // ACTIVE, INACTIVE
            $table->timestamps();

            $table->foreign('temple_id')->references('id')->on('temples')->onDelete('cascade');
            $table->unique(['tenant_id', 'temple_id', 'puja_id']);
            $table->index(['tenant_id', 'temple_id']);
            $table->index(['tenant_id', 'puja_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('temple_pujas');
    }
};
