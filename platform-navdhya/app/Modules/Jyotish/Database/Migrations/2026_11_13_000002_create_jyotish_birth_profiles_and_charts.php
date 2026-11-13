<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('jyotish_birth_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('user_id', 26)->nullable()->index();
            $table->string('name', 128);
            $table->string('gender', 16)->default('unspecified');
            $table->string('birth_place_text', 255);
            $table->string('place_id', 26)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('elevation_m')->default(0);
            $table->string('iana_timezone', 64);
            $table->dateTime('local_datetime');
            $table->bigInteger('utc_at_us');
            $table->double('jd_utc');
            $table->double('jd_tt');
            $table->double('delta_t_s');
            $table->string('accuracy_class', 32)->default('exact');
            $table->string('tradition', 32)->default('AMANTA');
            $table->json('provenance_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'user_id', 'created_at'], 'idx_birth_profile_tenant_user');
        });

        Schema::connection('tenant_shared')->create('jyotish_charts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->ulid('birth_profile_id')->index();
            $table->string('chart_type', 32)->default('D1');
            $table->string('ayanamsa_code', 32)->default('LAHIRI');
            $table->string('house_system', 32)->default('WHOLE_SIGN');
            $table->string('profile_hash', 64)->index();
            $table->decimal('lagna_degrees', 10, 7);
            $table->json('positions_json');
            $table->json('houses_json');
            $table->json('vargas_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'birth_profile_id', 'chart_type'], 'idx_chart_tenant_profile_type');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('jyotish_charts');
        Schema::connection('tenant_shared')->dropIfExists('jyotish_birth_profiles');
    }
};

