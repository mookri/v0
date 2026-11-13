<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('catalog_duration_rules', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('item_id', 26)->index();
            $table->string('scope', 32); // BASE | PER_PARTICIPANT | PACKAGE_TIER | TRADITION
            $table->string('scope_key', 50)->default('DEFAULT');
            $table->json('rule'); // {unit_minutes, per, multiplier, add_minutes, min_bound, max_bound}
            $table->timestamps();

            $table->unique(['tenant_id', 'item_id', 'scope', 'scope_key'], 'uq_duration_rule_scope');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('catalog_duration_rules');
    }
};

