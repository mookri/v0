<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consent ledger and notification preferences matrix (E1 / E4):
 * - consent_ledger: Append-only audit record of explicit devotee consent grants and revocations.
 * - notification_preferences: User x Category x Channel dispatch matrix with quiet hours windows.
 * - message_templates: Multi-channel message template registry with localized variants.
 * - events_export_cursors: Durable tracking for downstream analytics signal sync.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('tenant_shared')->create('consent_ledger', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('purpose', 32); // TRANSACTIONAL | RITUAL_REMINDERS | ANALYTICS | MARKETING
            $table->string('state', 32); // GRANTED | WITHDRAWN | EXPIRED
            $table->string('source', 64); // SIGNUP_FORM, SETTINGS_PORTAL, BOOKING_GATE, SUPPORT_DESK
            $table->string('policy_version', 16);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'user_id', 'purpose']);
        });

        Schema::connection('tenant_shared')->create('notification_preferences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('category', 32); // ACCOUNT | BOOKING | REMINDER | MARKETING
            $table->string('channel', 32); // IN_APP | SMS | WHATSAPP | EMAIL
            $table->boolean('is_enabled')->default(true);
            $table->string('quiet_hours_start', 8)->nullable(); // e.g. "21:00"
            $table->string('quiet_hours_end', 8)->nullable(); // e.g. "07:00"
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'category', 'channel'], 'notif_pref_user_cat_chan_unique');
        });

        Schema::connection('tenant_shared')->create('message_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('category', 32);
            $table->string('channel', 32);
            $table->string('locale', 8)->default('en');
            $table->string('template_name', 64);
            $table->text('body');
            $table->json('variables')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'category', 'channel', 'locale', 'template_name'], 'msg_tmpl_cat_chan_loc_unique');
        });

        Schema::connection('tenant_shared')->create('events_export_cursors', function (Blueprint $table): void {
            $table->string('feed_name', 64)->primary();
            $table->string('last_processed_ulid', 26)->nullable();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('events_export_cursors');
        Schema::connection('tenant_shared')->dropIfExists('message_templates');
        Schema::connection('tenant_shared')->dropIfExists('notification_preferences');
        Schema::connection('tenant_shared')->dropIfExists('consent_ledger');
    }
};
