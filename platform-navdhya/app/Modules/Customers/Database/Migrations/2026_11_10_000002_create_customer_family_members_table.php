<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devotee family members & sacred lineage (E1):
 * Distinguishes SELF, DECEASED ancestors (Shraddha rites), and THIRD_PARTY living relatives.
 * Living relatives cannot be notification targets without independent verified consent.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('customer_family_members', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id'); // Owning devotee
            $table->string('full_name', 120);
            $table->string('relation', 32); // SELF, SPOUSE, FATHER, MOTHER, SON, DAUGHTER, etc.
            $table->string('nakshatra', 32)->nullable();
            $table->string('rashi', 32)->nullable();
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable(); // For ancestral Shraddha observance
            $table->string('data_subject_kind', 32)->default('SELF'); // SELF | DECEASED | THIRD_PARTY
            $table->string('consent_ref', 64)->nullable(); // Legal basis reference / user declared authority
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'data_subject_kind']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('customer_family_members');
    }
};
