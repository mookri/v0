<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support messages (tenant_shared): the per-ticket thread. Customer
 * messages carry the author's user id; staff replies (future console)
 * will carry the staff user id the same way.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('support_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('ticket_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->text('body');
            $table->timestamps();

            $table->index(['tenant_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('support_messages');
    }
};
