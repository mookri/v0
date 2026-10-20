<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central authorization tables (TEN-003 §2.1 authorization group) landed
 * by Phase 0K T-0K01-01: roles, the capability registry with parse
 * columns (SEC-020), role<->capability grants, membership role
 * assignments and role-assignment scope bindings (SEC-021), and policies
 * with platform defaults (tenant_id NULL, LD-15).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->string('state', 24)->default('active'); // active | disabled
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::connection('central')->create('capability_metadata', function (Blueprint $table) {
            // The permission registry with structured parse columns
            // (SEC-020): every <module>:<resource>:<action> code resolves
            // to module/resource/action plus version + sensitive flag.
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 191);
            $table->string('name');
            $table->string('module', 48);
            $table->string('resource', 64);
            $table->string('action', 48);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('sensitive')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'module']);
            $table->index(['tenant_id', 'sensitive']);
        });

        Schema::connection('central')->create('role_permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('capability_code', 191);
            $table->timestamps();

            $table->unique(['role_id', 'capability_code']);
            $table->index(['tenant_id', 'capability_code']);
        });

        Schema::connection('central')->create('membership_role_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('membership_id')->constrained('tenant_memberships')->cascadeOnDelete();
            $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['membership_id', 'role_id']);
        });

        Schema::connection('central')->create('role_scope_bindings', function (Blueprint $table) {
            // SEC-021 role-assignment scoping: a role assignment may carry
            // scope_type/scope_id bindings resolved against hierarchy nodes
            // (hierarchy wiring lands in T-0K02-01).
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('role_assignment_id')->constrained('membership_role_assignments')->cascadeOnDelete();
            $table->string('scope_type', 48);
            $table->string('scope_id', 64);
            $table->string('scope_mode', 24)->nullable(); // SELF | NODE | DIRECT_CHILDREN | SUBTREE (HIER-005)
            $table->timestamps();

            $table->unique(['role_assignment_id', 'scope_type', 'scope_id'], 'role_scope_bindings_unique');
            $table->index(['tenant_id', 'scope_type']);
        });

        Schema::connection('central')->create('policies', function (Blueprint $table) {
            // SEC-021 policies; tenant_id NULL = platform default policy
            // (overridable, not silently removable — LD-15).
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('effect', 8); // allow | deny
            $table->json('capability_selector'); // {module, resource, action} pattern
            $table->json('scope_bindings')->nullable(); // [{scope_type, scope_id}]
            $table->json('conditions')->nullable(); // bounded declarative grammar (LD-15)
            $table->integer('priority')->default(0);
            $table->string('status', 16)->default('draft'); // draft | active | disabled
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('policies');
        Schema::connection('central')->dropIfExists('role_scope_bindings');
        Schema::connection('central')->dropIfExists('membership_role_assignments');
        Schema::connection('central')->dropIfExists('role_permissions');
        Schema::connection('central')->dropIfExists('capability_metadata');
        Schema::connection('central')->dropIfExists('roles');
    }
};
