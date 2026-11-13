<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoInteraction;
use App\Modules\Recommendations\Models\RecoTransition;
use App\Modules\Recommendations\Services\MarkovTransitionModel;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class SessionMarkovTest extends TenantIntegrationTestCase
{
    public function test_markov_model_trains_and_predicts_next_action(): void
    {
        // Session 1: Ganesha Puja -> Lakshmi Puja
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's1',
            'entity_type' => 'puja_service',
            'entity_id' => 'ganesha_puja',
            'interaction_type' => 'view_detail',
            'interacted_at' => now()->subMinutes(10),
        ]);
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's1',
            'entity_type' => 'puja_service',
            'entity_id' => 'lakshmi_puja',
            'interaction_type' => 'view_detail',
            'interacted_at' => now()->subMinutes(8),
        ]);

        // Session 2: Ganesha Puja -> Lakshmi Puja -> Saraswati Puja
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's2',
            'entity_type' => 'puja_service',
            'entity_id' => 'ganesha_puja',
            'interaction_type' => 'view_detail',
            'interacted_at' => now()->subMinutes(5),
        ]);
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's2',
            'entity_type' => 'puja_service',
            'entity_id' => 'lakshmi_puja',
            'interaction_type' => 'view_detail',
            'interacted_at' => now()->subMinutes(3),
        ]);
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'session_id' => 's2',
            'entity_type' => 'puja_service',
            'entity_id' => 'saraswati_puja',
            'interaction_type' => 'view_detail',
            'interacted_at' => now()->subMinute(),
        ]);

        $model = new MarkovTransitionModel();
        $trained = $model->train();

        $this->assertGreaterThan(0, $trained);

        $transition = RecoTransition::where('source_id', 'ganesha_puja')
            ->where('target_id', 'lakshmi_puja')
            ->first();

        $this->assertNotNull($transition);
        $this->assertEquals(2, $transition->count);
        $this->assertEquals(1.0, (float) $transition->p_estimate);

        $predictions = $model->predictNext('ganesha_puja', 5);
        $this->assertNotEmpty($predictions);
        $this->assertSame('lakshmi_puja', $predictions[0]['entity_id']);
        $this->assertEquals(1.0, $predictions[0]['probability']);
    }
}

