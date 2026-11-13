<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Application;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Feedback\Contracts\ReadsFeedbackQuality;
use App\Modules\Feedback\Domain\Aggregation\TrustAggregationEngine;
use App\Modules\Feedback\Models\FeedbackAggregationState;
use App\Modules\Feedback\Models\FeedbackInvitation;
use App\Modules\Feedback\Models\FeedbackScoreSnapshot;
use App\Modules\Feedback\Models\FeedbackSubjectAggregate;
use App\Modules\Feedback\Models\FeedbackSubmission;
use App\Modules\Incident\Contracts\ManagesIncidents;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Recomputes honest provider aggregates & snapshots, excluding external/venue/weather/platform
 * fault attribution (§6.3) and consuming verified arrival evidence (TS-01).
 */
final class RecomputeFeedbackQualityService implements ReadsFeedbackQuality
{
    public const ON_TIME_TOLERANCE_MINUTES = 15;

    /** @var list<string> */
    private const EXTERNAL_NON_PROVIDER_CAUSES = [
        'VENUE',
        'WEATHER_FORCE_MAJEURE',
        'PLATFORM',
        'DEVOTEE',
    ];

    public function __construct(
        private readonly ReadsBookings $bookings,
        private readonly ?ManagesIncidents $incidents = null,
    ) {
    }

    public function recomputeForSubject(string $subjectType, string $subjectId, string $category = 'default'): FeedbackSubjectAggregate
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        $submissions = FeedbackSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get();

        $bookingIds = $submissions->pluck('booking_id')->all();
        $aggStates = FeedbackAggregationState::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('submission_id', $submissions->pluck('id')->all())
            ->get()
            ->keyBy('submission_id');

        $incidentImpactsByBooking = [];
        if ($this->incidents !== null && $bookingIds !== []) {
            foreach ($this->incidents->impactsForBookings($bookingIds) as $impact) {
                $incidentImpactsByBooking[$impact['booking_id']] = $impact['attributed_cause'];
            }
        }

        $ratings = [];
        $timedSamples = [];
        $punctualSuccess = 0;
        $punctualTotal = 0;

        foreach ($submissions as $submission) {
            $state = $aggStates->get((string) $submission->getKey());
            $countsToward = $state !== null ? (array) $state->counts_toward : ['overall'];

            $cause = $incidentImpactsByBooking[$submission->booking_id] ?? null;
            if ($cause !== null && in_array($cause, self::EXTERNAL_NON_PROVIDER_CAUSES, true)) {
                if ($state !== null && ! $state->excluded_by_cause) {
                    $state->forceFill(['excluded_by_cause' => true, 'cause_code' => $cause])->save();
                }
                continue;
            }

            if ($state !== null && $state->excluded_by_cause) {
                continue;
            }

            if (! in_array('overall', $countsToward, true)) {
                continue;
            }

            $ratings[] = (int) $submission->overall_rating;
            $ageDays = max(0.0, (float) $submission->submitted_at->diffInHours($now) / 24.0);
            $timedSamples[] = ['rating' => (int) $submission->overall_rating, 'age_days' => $ageDays];

            $arrival = $this->bookings->arrivalForBooking($submission->booking_id);
            if ($arrival !== null && $arrival['delta_minutes'] !== null) {
                $punctualTotal++;
                if ((int) $arrival['delta_minutes'] <= self::ON_TIME_TOLERANCE_MINUTES) {
                    $punctualSuccess++;
                }
            }
        }

        $sampleN = count($ratings);
        $lifetimeMean = $sampleN > 0 ? round(array_sum($ratings) / $sampleN, 2) : null;
        $bayesian = TrustAggregationEngine::bayesianAverage($ratings, $category);
        $recentDecayed = TrustAggregationEngine::decayedAverage($timedSamples);
        $wilsonPunctual = TrustAggregationEngine::wilsonLowerBound($punctualSuccess, $punctualTotal);
        $confidence = TrustAggregationEngine::confidenceTier($sampleN);

        /** @var FeedbackSubjectAggregate $aggregate */
        $aggregate = FeedbackSubjectAggregate::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'period' => FeedbackSubjectAggregate::PERIOD_LIFETIME,
            ],
            [
                'sample_n' => $sampleN,
                'mean_average' => $lifetimeMean,
                'bayesian_average' => $bayesian,
                'wilson_punctuality_lower' => $wilsonPunctual,
                'wilson_complaint_free_lower' => null,
                'confidence_tier' => $confidence['tier'],
                'prior_code' => $category,
                'prior_version' => 'v1',
                'decay_lambda' => round(log(2.0) / TrustAggregationEngine::DEFAULT_HALF_LIFE_DAYS, 4),
                'computed_at' => $now->toDateTimeString(),
            ]
        );

        FeedbackSubjectAggregate::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'period' => FeedbackSubjectAggregate::PERIOD_RECENT_90D,
            ],
            [
                'sample_n' => $sampleN,
                'mean_average' => $recentDecayed,
                'bayesian_average' => $bayesian,
                'wilson_punctuality_lower' => $wilsonPunctual,
                'wilson_complaint_free_lower' => null,
                'confidence_tier' => $confidence['tier'],
                'prior_code' => $category,
                'prior_version' => 'v1',
                'decay_lambda' => round(log(2.0) / TrustAggregationEngine::DEFAULT_HALF_LIFE_DAYS, 4),
                'computed_at' => $now->toDateTimeString(),
            ]
        );

        FeedbackScoreSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'period' => FeedbackSubjectAggregate::PERIOD_LIFETIME,
            'sample_n' => $sampleN,
            'bayesian_average' => $bayesian,
            'wilson_punctuality_lower' => $wilsonPunctual,
            'confidence_tier' => $confidence['tier'],
            'prior_version' => 'v1',
            'snapshot_at' => $now->toDateTimeString(),
        ]);

        return $aggregate;
    }

    public function summaryForSubject(string $subjectType, string $subjectId): ?array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $lifetime = FeedbackSubjectAggregate::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('period', FeedbackSubjectAggregate::PERIOD_LIFETIME)
            ->first();

        $recent = FeedbackSubjectAggregate::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('period', FeedbackSubjectAggregate::PERIOD_RECENT_90D)
            ->first();

        if ($lifetime === null) {
            return [
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'sample_count' => 0,
                'confidence_tier' => 'NO_REVIEWS',
                'confidence_label' => 'No reviews yet',
                'bayesian_average' => null,
                'lifetime_average' => null,
                'recent_average' => null,
                'punctuality_wilson_lower' => null,
                'prior_version' => 'v1',
            ];
        }

        $confidence = TrustAggregationEngine::confidenceTier((int) $lifetime->sample_n);

        return [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'sample_count' => (int) $lifetime->sample_n,
            'confidence_tier' => $confidence['tier'],
            'confidence_label' => $confidence['label'],
            'bayesian_average' => $confidence['show_numeric_headline'] ? $lifetime->bayesian_average : null,
            'lifetime_average' => $confidence['show_numeric_headline'] ? $lifetime->mean_average : null,
            'recent_average' => $confidence['show_numeric_headline'] ? $recent?->mean_average : null,
            'punctuality_wilson_lower' => $lifetime->wilson_punctuality_lower,
            'prior_version' => $lifetime->prior_version,
        ];
    }

    public function hasActiveSafetySuppression(string $bookingId): bool
    {
        return FeedbackInvitation::query()
            ->where('booking_id', $bookingId)
            ->where('state', FeedbackInvitation::STATE_SUPPRESSED_BY_SAFETY)
            ->exists();
    }

    public function reviewSummaryForBooking(string $bookingId): ?array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $sub = FeedbackSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->first();

        if ($sub === null) {
            return null;
        }

        return [
            'review_reference' => $sub->review_reference,
            'overall_rating' => (int) $sub->overall_rating,
            'subject_type' => $sub->subject_type,
            'subject_id' => $sub->subject_id,
            'submitted_at' => $sub->submitted_at?->toJSON(),
        ];
    }
}

