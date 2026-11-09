<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devotee spiritual profiles (E1): Gotra, Nakshatra, Rashi, Kuladevata,
 * birth celestial coordinates, and ritual tradition (Amanta/Purnimanta).
 * Special-category spiritual data, strictly tenant-scoped and consent-gated.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('customer_spiritual_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('gotra', 64)->nullable();
            $table->string('nakshatra', 32)->nullable();
            $table->string('rashi', 32)->nullable();
            $table->string('kuladevata_name', 190)->nullable();
            $table->string('kuladevata_temple_id', 26)->nullable(); // Future hard-FK seam to temple_deities
            $table->date('birth_date')->nullable();
            $table->time('birth_time')->nullable();
            $table->string('birth_place', 120)->nullable();
            $table->string('birth_timezone', 64)->default('Asia/Kolkata');
            $table->string('tradition', 32)->default('PURNIMANTA'); // AMANTA | PURNIMANTA
            $table->timestamp('retention_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'tradition']);
            $table->index(['tenant_id', 'nakshatra']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('customer_spiritual_profiles');
    }
};
