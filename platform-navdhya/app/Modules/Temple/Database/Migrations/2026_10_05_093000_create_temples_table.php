<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temples (tenant_shared): temple profiles owned by partners.
 * One per partner per tenant, created via self-serve or admin.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('temples', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('slug', 190);
            $table->string('name', 255);
            $table->string('trust_name', 255);
            $table->string('type', 24); // 'individual' | 'trust'
            $table->text('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->json('images')->nullable();
            $table->json('amenities')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 24)->default('DRAFT'); // DRAFT, ACTIVE, INACTIVE
            $table->string('verification_status', 24)->default('PENDING'); // PENDING, VERIFIED, REJECTED
            $table->ulid('owner_id');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->unique(['tenant_id', 'owner_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'verification_status']);
            $table->index(['tenant_id', 'city']);
            $table->index(['tenant_id', 'state']);
            $table->index(['tenant_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('temples');
    }
};
