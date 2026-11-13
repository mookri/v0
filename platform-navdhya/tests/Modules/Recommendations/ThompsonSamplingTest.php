<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoBanditStat;
use App\Modules\Recommendations\Services\ThompsonSamplingBandit;
use Tests\TenantIntegrationTestCase;

class ThompsonSamplingTest extends TenantIntegrationTestCase
{
    public function test_thompson_sampling_updates_posteriors_and_selects_arm(): void
    {
        $bandit = new ThompsonSamplingBandit();

        // Entity 1: High performing (10 clicks, 5 impressions without click)
        for ($i = 0; $i < 10; $i++) {
            $bandit->recordFeedback('puja_service', 'puja_high', true);
        }
        for ($i = 0; $i < 5; $i++) {
            $bandit->recordFeedback('puja_service', 'puja_high', false);
        }

        // Entity 2: Poor performing (0 clicks, 20 impressions without click)
        for ($i = 0; $i < 20; $i++) {
            $bandit->recordFeedback('puja_service', 'puja_low', false);
        }

        $statHigh = RecoBanditStat::where('entity_id', 'puja_high')->first();
        $statLow = RecoBanditStat::where('entity_id', 'puja_low')->first();

        $this->assertNotNull($statHigh);
        $this->assertNotNull($statLow);
        $this->assertEquals(11, $statHigh->alpha); // 1 prior + 10
        $this->assertEquals(24, $statHigh->beta);  // 19 prior + 5
        $this->assertEquals(1, $statLow->alpha);   // 1 prior + 0
        $this->assertEquals(39, $statLow->beta);   // 19 prior + 20

        // In 50 Thompson sampling trials, puja_high should win significantly more than puja_low
        $candidates = [
            ['entity_type' => 'puja_service', 'entity_id' => 'puja_high'],
            ['entity_type' => 'puja_service', 'entity_id' => 'puja_low'],
        ];

        $wins = ['puja_high' => 0, 'puja_low' => 0];
        for ($t = 0; $t < 50; $t++) {
            $chosen = $bandit->selectArm($candidates);
            $this->assertNotNull($chosen);
            $wins[$chosen['entity_id']]++;
        }

        $this->assertGreaterThan($wins['puja_low'], $wins['puja_high']);
    }

    public function test_inject_exploration_slot_places_item_at_position_3(): void
    {
        $bandit = new ThompsonSamplingBandit();

        $rankedList = [
            ['entity_id' => 'p0', 'title' => 'Item 0'],
            ['entity_id' => 'p1', 'title' => 'Item 1'],
            ['entity_id' => 'p2', 'title' => 'Item 2'],
            ['entity_id' => 'p3', 'title' => 'Item 3'],
            ['entity_id' => 'p4', 'title' => 'Item 4'],
        ];

        $explorationItem = [
            'entity_id' => 'exp_1',
            'title' => 'Exploration Item',
            'reason_code' => 'DISCOVERY_EXPLORATION',
        ];

        $withExploration = $bandit->injectExplorationSlot($rankedList, $explorationItem, 3);

        $this->assertCount(6, $withExploration);
        $this->assertSame('exp_1', $withExploration[3]['entity_id']);
        $this->assertSame('p0', $withExploration[0]['entity_id']);
        $this->assertSame('p2', $withExploration[2]['entity_id']);
        $this->assertSame('p3', $withExploration[4]['entity_id']);
    }
}

