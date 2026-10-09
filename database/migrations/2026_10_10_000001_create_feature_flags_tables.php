<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feature-flag tables (doc 07 §10.1): `feature_flags` holds the global
 * defaults (code UNIQUE, kind module|feature|ops), `tenant_feature_flags`
 * holds per-tenant overrides (runtime kill switches). Both live on the
 * CENTRAL connection — flags are platform state, not tenant data, and a
 * global default can never commit inside a tenant transaction (TEN-003).
 *
 * Seeds the two roadmap flags (F3): PAYMENTS_ENABLED (B3 checkout) and
 * NOTIFY_EMAIL_ENABLED (N3 email channel), both default-off so the
 * default-safe branch (doc 07 §10.2 rule 1) holds until each track wires
 * its consumer. Evaluation order and the 60 s cache TTL live in
 * App\Platform\Features\FeatureFlags.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('central')->create('feature_flags', function (Blueprint $table) {
            $table->string('code', 64)->primary();
            $table->string('kind', 16)->default('feature'); // module|feature|ops
            $table->boolean('default')->default(false);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('tenant_feature_flags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 64);
            $table->ulid('tenant_id');
            $table->boolean('enabled');
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index('code');
        });

        $now = now();

        DB::connection('central')->table('feature_flags')->insert([
            [
                'code' => 'payments.enabled',
                'kind' => 'feature',
                'default' => false,
                'description' => 'Razorpay checkout (B3) — payment_intents, webhook confirm',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'notify.email',
                'kind' => 'feature',
                'default' => false,
                'description' => 'Email notification channel (N3) — Laravel mail dispatch',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_feature_flags');
        Schema::connection('central')->dropIfExists('feature_flags');
    }
};
