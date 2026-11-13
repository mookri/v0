<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoUserInterest;
use App\Modules\Recommendations\Models\RecoUserProfile;
use App\Modules\Recommendations\Services\UserInterestManager;
use Carbon\Carbon;
use Tests\TenantIntegrationTestCase;

class InterestProfilePrecedenceTest extends TenantIntegrationTestCase
{
    public function test_explicit_source_cannot_be_overwritten_by_inferred_source(): void
    {
        $userId = 4001;
        $manager = new UserInterestManager();

        // 1. Explicitly recorded tradition
        $manager->recordInterest(
            $userId,
            'tradition',
            'smartha',
            1.0,
            UserInterestManager::SOURCE_EXPLICIT
        );

        // 2. Later, automated inference tries to record a different tradition with weight 2.0
        $manager->recordInterest(
            $userId,
            'tradition',
            'smartha',
            2.0,
            UserInterestManager::SOURCE_INFERRED
        );

        $saved = RecoUserInterest::where('user_id', $userId)
            ->where('interest_kind', 'tradition')
            ->where('interest_key', 'smartha')
            ->first();

        $this->assertNotNull($saved);
        $this->assertSame(UserInterestManager::SOURCE_EXPLICIT, $saved->source);
        $this->assertEquals(1.0, $saved->weight);
    }

    public function test_intent_hierarchy_ranks_current_intent_above_recent_and_long_term(): void
    {
        $userId = 4002;
        $manager = new UserInterestManager();

        // Stored long term interest (created 10 days ago)
        Carbon::setTestNow(Carbon::now()->subDays(10));
        $manager->recordInterest(
            $userId,
            'deity',
            'shiva',
            2.0,
            UserInterestManager::SOURCE_EXPLICIT,
            30
        );

        Carbon::setTestNow(); // Reset to now

        $effective = $manager->getEffectiveInterests(
            $userId,
            'lakshmi_puja', // Current intent
            ['ganesha_puja', 'durga_puja'] // Recent session items
        );

        $this->assertNotEmpty($effective);

        // Current intent must be top
        $this->assertSame('intent', $effective[0]['kind']);
        $this->assertSame('lakshmi_puja', $effective[0]['key']);
        $this->assertEquals(10.0, $effective[0]['effective_weight']);

        // Recent session items next
        $this->assertSame('recent_session', $effective[1]['kind']);
        $this->assertSame('ganesha_puja', $effective[1]['key']);

        // Long term interest with decay next
        $shivaInterest = $effective->firstWhere('key', 'shiva');
        $this->assertNotNull($shivaInterest);
        $this->assertSame('deity', $shivaInterest['kind']);
    }

    public function test_disabled_personalization_returns_empty_interests(): void
    {
        $userId = 4003;
        RecoUserProfile::create([
            'user_id' => $userId,
            'personalization_enabled' => false,
        ]);

        $manager = new UserInterestManager();
        $manager->recordInterest($userId, 'tradition', 'vaishnava', 1.0, UserInterestManager::SOURCE_EXPLICIT);

        $effective = $manager->getEffectiveInterests($userId, 'puja_rudra');
        $this->assertEmpty($effective);
    }
}

