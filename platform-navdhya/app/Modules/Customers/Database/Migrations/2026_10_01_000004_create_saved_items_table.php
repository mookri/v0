<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved items (tenant_shared): bookmarks, not foreign keys. ref_type +
 * ref_id are shape-validated at write; existence is resolved by readers
 * (BFF enriches via catalog) so dangling refs never 500 — they resolve
 * to null upstream.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('saved_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('ref_type', 16);
            $table->string('ref_id', 64);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'ref_type', 'ref_id']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('saved_items');
    }
};
