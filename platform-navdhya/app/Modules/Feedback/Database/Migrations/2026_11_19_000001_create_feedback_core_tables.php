<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback core, three-record separation (submission / publication / aggregation),
 * invitations, dimensions, provider responses, appeals, integrity anomalies, and
 * statistical aggregates + immutable score snapshots on `tenant_shared`
 * (TS-05, TS-06, TS-07, TS-08, TS-09, ADR-0061, ADR-0062).
 */
return new class () extends Migration {
    public function up(): void
    {
        $schema = Schema::connection('tenant_shared');

        $schema->create('feedback_dimensions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('service_type', 64)->default('default');
            $table->string('code', 64);
            $table->string('label', 120);
            $table->unsignedTinyInteger('scale_min')->default(1);
            $table->unsignedTinyInteger('scale_max')->default(5);
            $table->json('counts_toward');
            $table->boolean('expert_only')->default(false);
            $table->string('active_version', 32)->default('v1');
            $table->timestamp('effective_from');
            $table->timestamps();

            $table->unique(['tenant_id', 'service_type', 'code', 'active_version'], 'fb_dim_unique_ver');
        });

        $schema->create('feedback_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('booking_id', 26)->unique();
            $table->unsignedBigInteger('invitee_user_id');
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->timestamp('window_opens_at');
            $table->timestamp('window_closes_at');
            $table->string('state', 32)->default('PENDING'); // PENDING | SENT | SUBMITTED | EXPIRED | SUPPRESSED_BY_SAFETY
            $table->string('channel', 32)->default('email');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'invitee_user_id', 'state']);
        });

        $schema->create('feedback_submissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('booking_id', 26)->unique();
            $table->string('review_reference', 32);
            $table->unsignedBigInteger('reviewer_user_id');
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('service_instance_id', 64);
            $table->string('booking_revision_id', 26)->nullable();
            $table->string('service_category', 64)->default('default');
            $table->unsignedTinyInteger('overall_rating');
            $table->text('original_text')->nullable(); // IMMUTABLE original verbatim text
            $table->boolean('share_publicly')->default(false);
            $table->json('edit_history')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('edit_window_closes_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'review_reference']);
            $table->index(['tenant_id', 'subject_type', 'subject_id']);
            $table->index(['tenant_id', 'reviewer_user_id']);
        });

        $schema->create('feedback_dimension_values', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('submission_id', 26);
            $table->string('dimension_code', 64);
            $table->unsignedTinyInteger('value');
            $table->string('dimension_version', 32)->default('v1');
            $table->timestamps();

            $table->index(['tenant_id', 'submission_id']);
        });

        $schema->create('feedback_publications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('submission_id', 26)->unique();
            $table->string('state', 32)->default('HELD'); // PUBLISHED | HELD | WITHHELD
            $table->text('redacted_text')->nullable(); // PII-masked copy; never overwrites original
            $table->string('reason_code', 64)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'state']);
        });

        $schema->create('feedback_moderation_actions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('submission_id', 26);
            $table->string('action', 32); // PUBLISH | REDACT | HOLD | WITHHOLD | CLEAR_ANOMALY
            $table->string('actor_id', 64);
            $table->string('reason_code', 64);
            $table->text('notes')->nullable();
            $table->string('policy_version', 32)->default('v1');
            $table->boolean('appealable')->default(true);
            $table->timestamp('occurred_at');

            $table->index(['tenant_id', 'submission_id']);
        });

        $schema->create('feedback_aggregation_state', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('submission_id', 26)->unique();
            $table->json('counts_toward');
            $table->boolean('excluded_by_cause')->default(false);
            $table->string('cause_code', 64)->nullable();
            $table->timestamps();
        });

        $schema->create('feedback_provider_responses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('submission_id', 26);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->unsignedBigInteger('author_user_id');
            $table->text('response_text');
            $table->string('state', 24)->default('PUBLISHED');
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'submission_id']);
        });

        $schema->create('feedback_appeals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('submission_id', 26);
            $table->string('appellant_id', 64);
            $table->string('appellant_role', 24); // devotee | provider
            $table->text('grounds');
            $table->string('status', 24)->default('PENDING'); // PENDING | UPHELD | OVERTURNED
            $table->string('decider_id', 64)->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'submission_id']);
        });

        $schema->create('feedback_anomalies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('submission_id', 26);
            $table->string('rule_id', 64);
            $table->json('signal_payload');
            $table->string('state', 24)->default('HELD'); // HELD | CLEARED | ESCALATED
            $table->string('reviewer_id', 64)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'submission_id']);
            $table->index(['tenant_id', 'state']);
        });

        $schema->create('feedback_subject_aggregates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('period', 24); // LIFETIME | RECENT_90D
            $table->unsignedInteger('sample_n')->default(0);
            $table->decimal('mean_average', 4, 2)->nullable();
            $table->decimal('bayesian_average', 4, 2)->nullable();
            $table->decimal('wilson_punctuality_lower', 5, 4)->nullable();
            $table->decimal('wilson_complaint_free_lower', 5, 4)->nullable();
            $table->string('confidence_tier', 32)->default('NO_REVIEWS');
            $table->string('prior_code', 64)->default('default');
            $table->string('prior_version', 32)->default('v1');
            $table->decimal('decay_lambda', 6, 4)->default(0.0150);
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'subject_type', 'subject_id', 'period'], 'fb_agg_subject_period');
        });

        $schema->create('feedback_score_snapshots', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('period', 24);
            $table->unsignedInteger('sample_n');
            $table->decimal('bayesian_average', 4, 2)->nullable();
            $table->decimal('wilson_punctuality_lower', 5, 4)->nullable();
            $table->string('confidence_tier', 32);
            $table->string('prior_version', 32);
            $table->timestamp('snapshot_at');

            $table->index(['tenant_id', 'subject_type', 'subject_id', 'snapshot_at'], 'fb_snap_subj_idx');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant_shared');
        $schema->dropIfExists('feedback_score_snapshots');
        $schema->dropIfExists('feedback_subject_aggregates');
        $schema->dropIfExists('feedback_anomalies');
        $schema->dropIfExists('feedback_appeals');
        $schema->dropIfExists('feedback_provider_responses');
        $schema->dropIfExists('feedback_aggregation_state');
        $schema->dropIfExists('feedback_moderation_actions');
        $schema->dropIfExists('feedback_publications');
        $schema->dropIfExists('feedback_dimension_values');
        $schema->dropIfExists('feedback_submissions');
        $schema->dropIfExists('feedback_invitations');
        $schema->dropIfExists('feedback_dimensions');
    }
};

