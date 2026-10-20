<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant registry v2 expansion (TEN-005, amendment C2).
 *
 * Additive only: new nullable columns + new tables. Legacy `code`/`state`
 * columns stay readable during the dual-read window; removal lands in a
 * later release after the backfill command confirms parity. SQLite +
 * MySQL compatible (Blueprint only, no raw SQL).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table): void {
            $table->string('slug', 63)->nullable()->unique()->after('code');
            $table->string('status', 20)->nullable()->after('state');
            $table->string('plan_code', 63)->nullable()->after('status');
            $table->string('db_connection_name', 63)->nullable()->after('db_mode');
            $table->text('db_config_ciphertext')->nullable()->after('db_connection_name');
            $table->char('base_currency', 3)->nullable()->after('db_config_ciphertext');
            $table->string('timezone', 63)->nullable()->after('base_currency');
            $table->string('locale', 10)->nullable()->after('timezone');
            $table->softDeletes()->after('updated_at');
        });

        Schema::connection('central')->create('plans', function (Blueprint $table): void {
            $table->string('code', 63)->primary();
            $table->string('name');
            $table->json('flag_bundle')->nullable();
            $table->string('rate_tier', 31)->default('standard');
            $table->json('limits')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('organizations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['tenant_id']);
        });

        Schema::connection('central')->create('org_memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('state', 24)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('org_memberships');
        Schema::connection('central')->dropIfExists('organizations');
        Schema::connection('central')->dropIfExists('plans');
        Schema::connection('central')->table('tenants', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'slug', 'status', 'plan_code', 'db_connection_name',
                'db_config_ciphertext', 'base_currency', 'timezone', 'locale',
            ]);
        });
    }
};
