<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pandit profiles (tenant_shared): the pandit's professional profile.
 * One per user per tenant, materialized at application approval.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('display_name', 190);
            $table->text('bio')->nullable();
            $table->json('languages')->nullable();
            $table->json('specializations')->nullable();
            $table->unsignedInteger('experience_years')->nullable();
            $table->json('certification_refs')->nullable();
            $table->string('verification_status', 24)->default('PENDING');
            $table->json('verification_documents')->nullable();
            $table->unsignedBigInteger('base_rate_minor')->nullable();
            $table->string('currency', 3)->default('INR');
            $table->string('market', 16)->default('in');
            $table->decimal('rating_avg', 2, 1)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('total_bookings')->default(0);
            $table->string('status', 24)->default('ACTIVE');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'verification_status']);
            $table->index(['tenant_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('pandit_profiles');
    }
};
