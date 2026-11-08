<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $conn = 'tenant_shared';

        // 1. Commercial Profiles
        Schema::connection($conn)->create('commercial_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('selling_model', 32)->default('PRINCIPAL'); // PRINCIPAL, AGENT
            $table->string('default_currency', 3)->default('INR');
            $table->unsignedInteger('commission_rate_bps')->default(1500); // 15.00%
            $table->bigInteger('platform_fee_minor')->default(0);
            $table->boolean('pass_through_payment_fees')->default(false);
            $table->timestamps();

            $table->unique('tenant_id', 'uq_commercial_profiles_tenant');
        });

        // 2. Commercial Policy Versions
        Schema::connection($conn)->create('commercial_policy_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->json('policy_json');
            $table->timestamps();

            $table->unique(['tenant_id', 'version'], 'uq_commercial_policy_tenant_ver');
            $table->index(['tenant_id', 'effective_from', 'effective_to'], 'idx_policy_effective');
        });
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        Schema::connection($conn)->dropIfExists('commercial_policy_versions');
        Schema::connection($conn)->dropIfExists('commercial_profiles');
    }
};

