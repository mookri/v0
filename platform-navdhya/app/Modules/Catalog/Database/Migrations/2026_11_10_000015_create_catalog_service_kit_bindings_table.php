<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('catalog_service_kit_bindings', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('service_item_id', 26)->index();
            $table->string('kit_item_id', 26)->index();
            $table->string('obligation', 30)->default('REQUIRED'); // REQUIRED | OPTIONAL | INCLUDED
            $table->unsignedSmallInteger('quantity_per_party')->default(1);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'service_item_id', 'kit_item_id'], 'uq_service_kit_binding');
            $table->index(['tenant_id', 'kit_item_id'], 'idx_bindings_kit_item');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('catalog_service_kit_bindings');
    }
};

