<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner applications (onboarding module, stage 1 track engine port):
 * tenant-scoped, version-pinned track, full lifecycle states. Table is
 * discovered by tenant promotion via its tenant_id column (no registry).
 */
return new class () extends Migration {
    public function up(): void
    {
        // Tenant data connection (TEN-002): module tenant tables live on
        // tenant_shared; dedicated tenants receive the identical schema at
        // provisioning (promotion copies rows, never schema).
        Schema::connection('tenant_shared')->create('partner_applications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('identity_id', 64);
            $table->string('track', 32);
            $table->string('track_version', 64);
            $table->string('status', 24)->default('DRAFT');
            $table->string('market', 16)->default('in');
            $table->string('supersedes', 26)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewer_id', 64)->nullable();
            $table->string('rejection_code', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'identity_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('partner_applications');
    }
};
