<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temple managers (tenant_shared): links users to temples with roles.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('temple_managers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('temple_id');
            $table->ulid('user_id');
            $table->string('role', 24); // owner, admin, manager, viewer
            $table->timestamp('invited_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('temple_id')->references('id')->on('temples')->onDelete('cascade');
            $table->unique(['tenant_id', 'temple_id', 'user_id']);
            $table->index(['tenant_id', 'temple_id']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('temple_managers');
    }
};
