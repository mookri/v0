<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand temple schema for deities, aarti schedules, sanctum rules, venue limits,
 * priests, puja slot types, and council booking approvals (Program C, C1).
 */
return new class () extends Migration {
    public function up(): void
    {
        // 1. Extend temples
        Schema::connection('tenant_shared')->table('temples', function (Blueprint $table): void {
            $table->string('timezone', 64)->default('Asia/Kolkata')->after('lng');
            $table->unsignedInteger('daily_booking_cap')->nullable()->after('timezone');
        });

        // 2. Temple Deities
        Schema::connection('tenant_shared')->create('temple_deities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('temple_id');
            $table->string('name', 190);
            $table->string('lineage', 100)->nullable();
            $table->string('iconography_url', 500)->nullable();
            $table->string('status', 24)->default('ACTIVE');
            $table->timestamps();

            $table->foreign('temple_id')->references('id')->on('temples')->onDelete('cascade');
            $table->index(['tenant_id', 'temple_id']);
            $table->index(['tenant_id', 'status']);
        });

        // 3. Aarti Schedules
        Schema::connection('tenant_shared')->create('temple_aarti_schedules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('temple_id');
            $table->ulid('deity_id')->nullable();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday..6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('blocks_darshan')->default(true);
            $table->boolean('blocks_homa')->default(true);
            $table->timestamps();

            $table->foreign('temple_id')->references('id')->on('temples')->onDelete('cascade');
            $table->foreign('deity_id')->references('id')->on('temple_deities')->nullOnDelete();
            $table->index(['tenant_id', 'temple_id']);
            $table->index(['tenant_id', 'temple_id', 'day_of_week']);
        });

        // 4. Sanctum Rules
        Schema::connection('tenant_shared')->create('temple_sanctum_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('temple_id');
            $table->ulid('deity_id')->nullable();
            $table->unsignedInteger('max_occupancy_units')->default(10);
            $table->json('entry_constraints')->nullable();
            $table->boolean('allows_multiple_events')->default(false);
            $table->timestamps();

            $table->foreign('temple_id')->references('id')->on('temples')->onDelete('cascade');
            $table->foreign('deity_id')->references('id')->on('temple_deities')->nullOnDelete();
            $table->index(['tenant_id', 'temple_id']);
        });

        // 5. Venue Limits
        Schema::connection('tenant_shared')->create('temple_venue_limits', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('temple_id');
            $table->string('scope', 32); // CONCURRENT_EVENTS, DAILY_HEADCOUNT, DAILY_BOOKINGS, SLOT_TYPE
            $table->string('slot_type', 32)->nullable();
            $table->unsignedInteger('limit_units');
            $table->timestamps();

            $table->foreign('temple_id')->references('id')->on('temples')->onDelete('cascade');
            $table->index(['tenant_id', 'temple_id', 'scope']);
        });

        // 6. Temple Priests
        Schema::connection('tenant_shared')->create('temple_priests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('temple_id');
            $table->string('pandit_id', 26)->nullable(); // soft cross-module reference
            $table->unsignedBigInteger('user_id');
            $table->string('role', 32)->default('PRIEST'); // HEAD_PRIEST, PRIEST, ASSISTANT
            $table->boolean('council_member')->default(false);
            $table->timestamps();

            $table->foreign('temple_id')->references('id')->on('temples')->onDelete('cascade');
            $table->index(['tenant_id', 'temple_id']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'pandit_id']);
        });

        // 7. Extend temple_pujas
        Schema::connection('tenant_shared')->table('temple_pujas', function (Blueprint $table): void {
            $table->string('slot_type', 32)->default('PUJA')->after('capacity'); // PUJA, DARSHAN, HOMA
            $table->unsignedInteger('min_persons')->default(1)->after('slot_type');
            $table->unsignedInteger('max_persons')->default(1)->after('min_persons');
            $table->boolean('requires_approval')->default(false)->after('max_persons');
            $table->unsignedInteger('approval_sla_minutes')->default(120)->after('requires_approval');

            $table->index(['tenant_id', 'temple_id', 'slot_type']);
        });

        // 8. Temple Booking Approvals
        Schema::connection('tenant_shared')->create('temple_booking_approvals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('booking_id', 26);
            $table->ulid('temple_id');
            $table->string('revision_id', 26)->nullable();
            $table->string('state', 32)->default('PENDING'); // PENDING, APPROVED, DECLINED, ESCALATED, EXPIRED
            $table->string('required_role', 32)->default('HEAD_PRIEST');
            $table->dateTime('sla_expires_at');
            $table->dateTime('hard_expires_at');
            $table->unsignedBigInteger('decision_by')->nullable();
            $table->string('decision_reason', 500)->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('temple_id')->references('id')->on('temples')->onDelete('cascade');
            $table->index(['tenant_id', 'booking_id']);
            $table->index(['tenant_id', 'temple_id', 'state']);
            $table->index(['tenant_id', 'state', 'hard_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('temple_booking_approvals');

        Schema::connection('tenant_shared')->table('temple_pujas', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'temple_id', 'slot_type']);
            $table->dropColumn([
                'slot_type',
                'min_persons',
                'max_persons',
                'requires_approval',
                'approval_sla_minutes',
            ]);
        });

        Schema::connection('tenant_shared')->dropIfExists('temple_priests');
        Schema::connection('tenant_shared')->dropIfExists('temple_venue_limits');
        Schema::connection('tenant_shared')->dropIfExists('temple_sanctum_rules');
        Schema::connection('tenant_shared')->dropIfExists('temple_aarti_schedules');
        Schema::connection('tenant_shared')->dropIfExists('temple_deities');

        Schema::connection('tenant_shared')->table('temples', function (Blueprint $table): void {
            $table->dropColumn([
                'timezone',
                'daily_booking_cap',
            ]);
        });
    }
};

