<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $conn = 'tenant_shared';
        if (! Schema::connection($conn)->hasTable('catalog_package_tiers')) {
            Schema::connection($conn)->create('catalog_package_tiers', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('code', 32)->unique();
                $table->string('name', 64);
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('sort')->default(0);
                $table->string('status', 16)->default('ACTIVE');
                $table->timestamps();
            });

            // Seed canonical 3 package tiers (F4)
            $now = now();
            DB::connection($conn)->table('catalog_package_tiers')->insert([
                [
                    'id' => (string) Str::ulid(),
                    'code' => 'STANDARD',
                    'name' => 'Standard Puja',
                    'description' => 'Essential rituals and verified samagri kit.',
                    'sort' => 10,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => (string) Str::ulid(),
                    'code' => 'PREMIUM',
                    'name' => 'Premium Puja',
                    'description' => 'Enhanced rituals, extended sacred recitations and premium samagri.',
                    'sort' => 20,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => (string) Str::ulid(),
                    'code' => 'COMPLETE',
                    'name' => 'Complete Puja',
                    'description' => 'Comprehensive multi-priest ceremony with supreme samagri offerings.',
                    'sort' => 30,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        Schema::connection($conn)->dropIfExists('catalog_package_tiers');
    }
};

