<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central tenancy registry tables (doc 02, TEN-005/TEN-006): the tenant
 * record and user<->tenant memberships. Central connection only; central
 * tables never contain tenant business data (TEN-003).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('db_mode', 16)->default('shared'); // shared | dedicated (TEN-002)
            $table->string('state', 24)->default('active'); // active | suspended | deprovisioned (TEN-015)
            $table->timestamps();
        });

        Schema::connection('central')->create('tenant_memberships', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Organization-hierarchy node binding (K-002); the typed
            // hierarchy tables land in T-0K02-01.
            $table->ulid('org_node_id')->nullable();
            $table->string('state', 24)->default('active'); // active | suspended | expired
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['user_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_memberships');
        Schema::connection('central')->dropIfExists('tenants');
    }
};
