<?php

declare(strict_types=1);

namespace Tests\Modules\Feedback;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\PanditArrival;
use App\Modules\Feedback\Application\RecomputeFeedbackQualityService;
use App\Modules\Feedback\Application\SubmitFeedbackUseCase;
use App\Modules\Feedback\Domain\Aggregation\TrustAggregationEngine;
use App\Modules\Feedback\Models\FeedbackSubjectAggregate;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

final class QualityAggregationTest extends TenantIntegrationTestCase
{
    public function test_pure_bayesian_wilson_and_confidence_tier_math(): void
    {
        // Single 5-star review pulls toward category prior rather than 5.0 headline
        $bayesian = TrustAggregationEngine::bayesianAverage([5], 'default', 10);
        $this->assertEqualsWithDelta(4.27, $bayesian, 0.01);

        // Confidence tier suppresses numeric headline below N=5
        $newTier = TrustAggregationEngine::confidenceTier(2);
        $this->assertSame('NEW_PROVIDER', $newTier['tier']);
        $this->assertFalse($newTier['show_numeric_headline']);

        $emerging = TrustAggregationEngine::confidenceTier(5);
        $this->assertSame('EMERGING_CONFIDENCE', $emerging['tier']);
        $this->assertTrue($emerging['show_numeric_headline']);

        // Wilson lower bound penalizes tiny sample 1/1 vs 19/20
        $smallWilson = TrustAggregationEngine::wilsonLowerBound(1, 1);
        $largeWilson = TrustAggregationEngine::wilsonLowerBound(19, 20);
        $this->assertNotNull($smallWilson);
        $this->assertNotNull($largeWilson);
        $this->assertLessThan($largeWilson, $smallWilson);

        // Decay favors recent 5-star over 180-day-old 1-star
        $decayed = TrustAggregationEngine::decayedAverage([
            ['rating' => 5, 'age_days' => 1.0],
            ['rating' => 1, 'age_days' => 180.0],
        ]);
        $this->assertGreaterThan(3.5, (float) $decayed);
    }

    public function test_recompute_hides_headline_for_new_provider_and_exposes_headline_plus_wilson_at_five_samples(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);

        $this->resolveContext($tenant);
        try {
            $pandit = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE]);
            $submitter = app(SubmitFeedbackUseCase::class);
            $recompute = app(RecomputeFeedbackQualityService::class);

            $createReviewedBooking = function (int $rating, int $deltaMinutes) use ($tenant, $devotee, $pandit, $submitter): void {
                $scheduled = CarbonImmutable::now('UTC')->subDays(2);
                $booking = Booking::factory()->create([
                    'user_id' => $devotee->id,
                    'status' => BookingLifecycle::STATUS_COMPLETED,
                    'scheduled_at' => $scheduled->toDateTimeString(),
                ]);
                PanditAssignment::factory()->create([
                    'tenant_id' => (string) $tenant->getKey(),
                    'pandit_id' => (string) $pandit->getKey(),
                    'booking_id' => (string) $booking->getKey(),
                    'status' => PanditAssignment::STATUS_COMPLETED,
                    'assigned_at' => $scheduled->subHour(),
                    'confirmed_at' => $scheduled->subHour(),
                ]);
                PanditArrival::query()->create([
                    'tenant_id' => (string) $tenant->getKey(),
                    'booking_id' => (string) $booking->getKey(),
                    'pandit_id' => (string) $pandit->getKey(),
                    'arrived_at' => $scheduled->addMinutes($deltaMinutes)->toDateTimeString(),
                    'recorded_by_actor_id' => (string) $devotee->id,
                    'delta_minutes' => $deltaMinutes,
                    'source_channel' => PanditArrival::SOURCE_PARTNER_APP,
                ]);
                $submitter->execute([
                    'booking_id' => (string) $booking->getKey(),
                    'acting_user_id' => $devotee->id,
                    'overall_rating' => $rating,
                    'share_publicly' => true,
                ]);
            };

            // 1 single 5-star review -> NEW_PROVIDER, headline numeric fields MUST be null
            $createReviewedBooking(5, 3);
            $recompute->recomputeForSubject('pandit', (string) $pandit->getKey());

            $summary1 = $recompute->summaryForSubject('pandit', (string) $pandit->getKey());
            $this->assertNotNull($summary1);
            $this->assertSame(1, $summary1['sample_count']);
            $this->assertSame('NEW_PROVIDER', $summary1['confidence_tier']);
            $this->assertNull($summary1['bayesian_average']);
            $this->assertNull($summary1['lifetime_average']);

            // Reach N=5 -> EMERGING_CONFIDENCE unlocks Bayesian + Wilson punctuality
            $createReviewedBooking(5, 5);
            $createReviewedBooking(4, 10);
            $createReviewedBooking(5, 25); // late (>15m)
            $createReviewedBooking(5, -2);
            $recompute->recomputeForSubject('pandit', (string) $pandit->getKey());

            $summary5 = $recompute->summaryForSubject('pandit', (string) $pandit->getKey());
            $this->assertSame(5, $summary5['sample_count']);
            $this->assertSame('EMERGING_CONFIDENCE', $summary5['confidence_tier']);
            $this->assertNotNull($summary5['bayesian_average']);
            $this->assertLessThan(4.8, (float) $summary5['bayesian_average']); // pulled toward prior 4.2
            $this->assertEqualsWithDelta(TrustAggregationEngine::wilsonLowerBound(4, 5), $summary5['punctuality_wilson_lower'], 0.001);

            $agg = FeedbackSubjectAggregate::query()
                ->where('subject_id', (string) $pandit->getKey())
                ->where('period', FeedbackSubjectAggregate::PERIOD_LIFETIME)
                ->firstOrFail();
            $this->assertSame('v1', $agg->prior_version);
        } finally {
            $this->clearContext();
        }
    }
}
