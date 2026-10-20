<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor profiles (P12): partner-run storefronts bound to one APPROVED
 * partner-vendor application (unique per tenant) and one owning user.
 * application_id / owner_user_id are soft references — onboarding lives
 * in another module and users sit on the central connection (same
 * posture as catalog vendor_id: no cross-module FKs).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('vendors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('application_id', 26);
            $table->unsignedBigInteger('owner_user_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('status', 24)->default('ACTIVE'); // ACTIVE | SUSPENDED
            $table->string('verification_status', 24)->default('PENDING'); // PENDING | VERIFIED
            $table->timestamps();

            $table->unique(['tenant_id', 'application_id']);
            $table->index(['tenant_id', 'owner_user_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->drop('vendors');
    }
};
