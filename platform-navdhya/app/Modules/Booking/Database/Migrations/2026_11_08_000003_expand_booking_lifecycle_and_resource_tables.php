<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking Lifecycle, Revisions & Resource Calendars (Phase 2, Tasks 2.1 & 2.2).
 */
return new class () extends Migration {
    public function up(): void
    {
        $conn = 'tenant_shared';

        // 1. Extend bookings table
        Schema::connection($conn)->table('bookings', function (Blueprint $table): void {
            $table->ulid('current_revision_id')->nullable()->after('version');
            $table->timestamp('checkout_expires_at')->nullable()->after('current_revision_id');
            $table->timestamp('service_start_at')->nullable()->after('checkout_expires_at');
            $table->timestamp('service_end_at')->nullable()->after('service_start_at');
            $table->string('service_timezone', 64)->nullable()->after('service_end_at');
            $table->ulid('rebooked_from_id')->nullable()->after('service_timezone');
            $table->string('accepted_quote_hash', 64)->nullable()->after('rebooked_from_id');

            $table->index(['tenant_id', 'service_start_at', 'service_end_at']);
        });

        // 2. Booking Revisions
        Schema::connection($conn)->create('booking_revisions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('booking_id');
            $table->unsignedInteger('version')->default(1);
            $table->string('quote_id', 26)->nullable();
            $table->string('commercial_policy_version_id', 26)->nullable();
            $table->json('service_address_json')->nullable();
            $table->string('service_mode', 32)->default('AT_LOCATION');
            $table->timestamps();

            $table->unique(['tenant_id', 'booking_id', 'version'], 'uq_booking_revisions_ver');
            $table->index(['tenant_id', 'booking_id']);
        });

        // 3. Booking Lines
        Schema::connection($conn)->create('booking_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('revision_id');
            $table->unsignedInteger('line_number')->default(1);
            $table->string('line_kind', 32)->default('SERVICE');
            $table->string('sku', 64);
            $table->string('variant_id', 64)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('server_duration_minutes')->default(60);
            $table->string('selected_pandit_id', 26)->nullable();
            $table->string('selected_temple_id', 26)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'revision_id']);
            $table->index(['tenant_id', 'selected_pandit_id']);
            $table->index(['tenant_id', 'selected_temple_id']);
        });

        // 4. Resource Calendars
        Schema::connection($conn)->create('resource_calendars', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('resource_type', 32); // PANDIT, TEMPLE, STOCK
            $table->string('resource_id', 64);
            $table->string('timezone', 64)->default('UTC');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'resource_type', 'resource_id'], 'uq_resource_calendars_res');
        });

        // 5. Resource Allocations
        Schema::connection($conn)->create('resource_allocations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('calendar_id');
            $table->ulid('booking_id')->nullable();
            $table->ulid('revision_id')->nullable();
            $table->timestamp('interval_start');
            $table->timestamp('interval_end');
            $table->unsignedInteger('units')->default(1);
            $table->string('status', 24)->default('HELD'); // HELD, COMMITTED, RELEASED, EXPIRED
            $table->timestamp('expires_at')->nullable();
            $table->string('release_reason', 64)->nullable();
            $table->timestamps();

            $table->index(
                ['tenant_id', 'calendar_id', 'status', 'interval_start', 'interval_end'],
                'idx_allocations_lookup'
            );
            $table->index(['tenant_id', 'status', 'expires_at'], 'idx_allocations_expiry');
            $table->index(['tenant_id', 'booking_id'], 'idx_allocations_booking');
        });

        // 6. Stock Reservations
        Schema::connection($conn)->create('stock_reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('vendor_id', 64);
            $table->string('sku', 64);
            $table->ulid('booking_id')->nullable();
            $table->ulid('revision_id')->nullable();
            $table->unsignedInteger('units')->default(1);
            $table->string('status', 24)->default('HELD'); // HELD, COMMITTED, CONSUMED, RELEASED, EXPIRED
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'vendor_id', 'sku', 'status'], 'idx_stock_res_vendor');
            $table->index(['tenant_id', 'booking_id'], 'idx_stock_res_booking');
        });

        // 7. Booking Changes
        Schema::connection($conn)->create('booking_changes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('booking_id');
            $table->string('kind', 32); // RESCHEDULE, CANCELLATION
            $table->ulid('proposed_revision_id')->nullable();
            $table->string('old_quote_id', 26)->nullable();
            $table->string('new_quote_id', 26)->nullable();
            $table->bigInteger('price_difference_minor')->default(0);
            $table->bigInteger('fee_minor')->default(0);
            $table->bigInteger('refund_minor')->default(0);
            $table->string('status', 32)->default('PENDING_APPROVAL');
            $table->string('requested_by', 64);
            $table->string('approved_by', 64)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'booking_id', 'status'], 'idx_booking_changes_status');
        });
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        Schema::connection($conn)->dropIfExists('booking_changes');
        Schema::connection($conn)->dropIfExists('stock_reservations');
        Schema::connection($conn)->dropIfExists('resource_allocations');
        Schema::connection($conn)->dropIfExists('resource_calendars');
        Schema::connection($conn)->dropIfExists('booking_lines');
        Schema::connection($conn)->dropIfExists('booking_revisions');

        Schema::connection($conn)->table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'service_start_at', 'service_end_at']);
            $table->dropColumn([
                'current_revision_id',
                'checkout_expires_at',
                'service_start_at',
                'service_end_at',
                'service_timezone',
                'rebooked_from_id',
                'accepted_quote_hash',
            ]);
        });
    }
};

