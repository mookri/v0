<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    protected $connection = 'tenant_shared';

    public function up(?string $connection = null): void
    {
        Schema::connection($connection ?? $this->getConnection())->table('bookings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('idempotency_hash_version')->default(1);
        });
    }

    public function down(): void
    {
        throw new LogicException('Booking request fingerprints require forward repair.');
    }
};
