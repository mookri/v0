<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoImpression;
use App\Modules\Recommendations\Models\RecoInteraction;
use App\Modules\Recommendations\Services\CollaborativeFilteringAggregator;
use App\Modules\Recommendations\Services\FraudGuard;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class FraudGuardTest extends TenantIntegrationTestCase
{
    public function test_fraud_guard_verifies_interactions_with_preexisting_impressions(): void
    {
        $sessionId = 'sess_valid_1';
        $entityId = 'puja_ganesha';

        RecoImpression::create([
            'id' => (string) Str::ulid(),
            'surface' => 'home_pujas',
            'session_id' => $sessionId,
            'entity_type' => 'puja_service',
            'entity_id' => $entityId,
            'position' => 0,
            'reason_code' => 'TRENDING_IN_CITY',
            'impressed_at' => now(),
        ]);

        $guard = new FraudGuard();
        $result = $guard->validateInteraction([
            'session_id' => $sessionId,
            'entity_type' => 'puja_service',
            'entity_id' => $entityId,
            'dwell_ms' => 1200,
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame(FraudGuard::STATUS_VERIFIED, $result['status']);
    }

    public function test_fraud_guard_rejects_clicks_without_preexisting_impression(): void
    {
        $guard = new FraudGuard();

        // Ghost click without any impression
        $result = $guard->validateInteraction([
            'session_id' => 'sess_ghost_click',
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_fabricated_target',
            'dwell_ms' => 500,
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame(FraudGuard::STATUS_UNVERIFIED_EXPOSURE, $result['status']);
    }

    public function test_fraud_guard_flags_rapid_sub_human_clicks(): void
    {
        $sessionId = 'sess_rapid_bot';
        $entityId = 'puja_navgraha';

        RecoImpression::create([
            'id' => (string) Str::ulid(),
            'surface' => 'home_pujas',
            'session_id' => $sessionId,
            'entity_type' => 'puja_service',
            'entity_id' => $entityId,
            'position' => 1,
            'reason_code' => 'POPULAR',
            'impressed_at' => now(),
        ]);

        $guard = new FraudGuard();
        $result = $guard->validateInteraction([
            'session_id' => $sessionId,
            'entity_type' => 'puja_service',
            'entity_id' => $entityId,
            'dwell_ms' => 10, // Under 50ms threshold
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame(FraudGuard::STATUS_SUSPECT, $result['status']);
    }

    public function test_unverified_interactions_are_excluded_from_cf_computation(): void
    {
        // 2 sessions viewing item A & B
        // Session 1: Both verified
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's_verified',
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_a',
            'interaction_type' => 'view_detail',
            'status' => 'verified',
            'interacted_at' => now(),
        ]);
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's_verified',
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_b',
            'interaction_type' => 'view_detail',
            'status' => 'verified',
            'interacted_at' => now(),
        ]);

        // Session 2: Click on item C is unverified spam
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's_spam',
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_a',
            'interaction_type' => 'view_detail',
            'status' => 'unverified_exposure',
            'interacted_at' => now(),
        ]);
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's_spam',
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_c_spam',
            'interaction_type' => 'view_detail',
            'status' => 'unverified_exposure',
            'interacted_at' => now(),
        ]);

        $aggregator = new CollaborativeFilteringAggregator();
        $aggregator->compute();

        // Neighbor between A and B should exist
        $this->assertDatabaseHas('reco_item_neighbors', [
            'source_id' => 'puja_a',
            'neighbor_id' => 'puja_b',
        ], 'central');

        // Neighbor between A and C must NOT exist because C was unverified
        $this->assertDatabaseMissing('reco_item_neighbors', [
            'source_id' => 'puja_a',
            'neighbor_id' => 'puja_c_spam',
        ], 'central');
    }
}
