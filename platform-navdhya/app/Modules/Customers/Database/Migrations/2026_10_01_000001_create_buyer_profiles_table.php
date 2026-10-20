<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer profiles (customers module identity anchor, ADR-0005): one row
 * per (tenant, user) materialized at signup/invite-accept. Customers are
 * platform users with a profile attached — never a second user system.
 */
return new class () extends Migration {
    public function up(): void
    {
        // Tenant data connection (TEN-002): module tenant tables live on
        // tenant_shared; dedicated tenants receive the identical schema at
        // provisioning (promotion copies rows, never schema).
        Schema::connection('tenant_shared')->create('buyer_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('display_name', 190)->nullable();
            $table->string('locale', 8)->default('en');
            $table->string('status', 16)->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('buyer_profiles');
    }
};
