<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provenance, user telemetry events, and devotee insights (E1 / E5):
 * - profile_field_provenance: Tracks source confidence and expiry per profile field.
 * - user_events: Telemetry spine with 2KB payload guard and origin_outbox_id deduplication.
 * - customer_insights: Aggregated devotee intelligence for Customer-360 support reads.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('profile_field_provenance', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('field_name', 64);
            $table->string('source', 32); // USER_ENTERED | INFERRED | DERIVED | THIRD_PARTY
            $table->smallInteger('confidence')->default(100); // 0 to 100
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'field_name']);
        });

        Schema::connection('tenant_shared')->create('user_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_type', 64);
            $table->json('properties'); // Strictly capped <= 2,048 bytes
            $table->json('context')->nullable();
            $table->string('source', 32); // WEB_COLLECTOR | SERVER_OUTBOX | API
            $table->string('purpose', 32)->default('ANALYTICS');
            $table->string('origin_outbox_id', 36)->nullable()->unique();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'event_type', 'occurred_at']);
            $table->index(['tenant_id', 'user_id', 'occurred_at']);
        });

        Schema::connection('tenant_shared')->create('customer_insights', function (Blueprint $table): void {
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->timestamp('last_active_at')->nullable();
            $table->integer('booking_cadence_days')->nullable();
            $table->json('category_affinity')->nullable();
            $table->smallInteger('reminder_engagement_rate')->default(0);
            $table->timestamp('updated_at')->useCurrent();

            $table->primary(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('customer_insights');
        Schema::connection('tenant_shared')->dropIfExists('user_events');
        Schema::connection('tenant_shared')->dropIfExists('profile_field_provenance');
    }
};
