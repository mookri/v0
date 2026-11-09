<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand pandit_profiles with Vedic taxonomy and operational buffer attributes.
 * Also create pandit_shakhas reference table.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('pandit_shakhas', function (Blueprint $table): void {
            $table->string('code', 32)->primary();
            $table->string('veda', 32);
            $table->string('name', 128);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('veda');
            $table->index('is_active');
        });

        Schema::connection('tenant_shared')->table('pandit_profiles', function (Blueprint $table): void {
            $table->string('tradition', 32)->nullable()->after('display_name');
            $table->string('gotra', 64)->nullable()->after('tradition');
            $table->string('shakha_code', 32)->nullable()->after('gotra');
            $table->unsignedInteger('travel_buffer_minutes')->default(30)->after('verification_documents');
            $table->unsignedInteger('travel_radius_km')->default(25)->after('travel_buffer_minutes');
            $table->string('timezone', 64)->default('Asia/Kolkata')->after('travel_radius_km');

            $table->index(['tenant_id', 'tradition']);
            $table->index(['tenant_id', 'gotra']);
            $table->index(['tenant_id', 'shakha_code']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->table('pandit_profiles', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'tradition']);
            $table->dropIndex(['tenant_id', 'gotra']);
            $table->dropIndex(['tenant_id', 'shakha_code']);
            $table->dropColumn([
                'tradition',
                'gotra',
                'shakha_code',
                'travel_buffer_minutes',
                'travel_radius_km',
                'timezone',
            ]);
        });

        Schema::connection('tenant_shared')->dropIfExists('pandit_shakhas');
    }
};

