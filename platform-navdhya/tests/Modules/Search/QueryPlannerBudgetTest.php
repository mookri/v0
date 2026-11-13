<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Planning\QueryPlanner;
use Tests\TenantIntegrationTestCase;

class QueryPlannerBudgetTest extends TenantIntegrationTestCase
{
    public function test_query_planner_extracts_features_and_determines_stages(): void
    {
        $planner = new QueryPlanner();

        // 1. Sanskrit query with diacritics
        $plan1 = $planner->plan('rudrābhiṣeka puja', 'puja_service', 'Varanasi', 'en');
        $this->assertSame('rudrābhiṣeka puja', $plan1->queryRaw);
        $this->assertTrue($plan1->features['has_diacritics']);
        $this->assertSame('iast', $plan1->features['script_family']);
        $this->assertContains('scheme_equivalence', $plan1->stages);
        $this->assertContains('inverted_intersect', $plan1->stages);

        // 2. Devanagari query
        $plan2 = $planner->plan('रुद्राभिषेक');
        $this->assertSame('devanagari', $plan2->features['script_family']);
        $this->assertContains('ngram', $plan2->stages);
        $this->assertContains('scheme_equivalence', $plan2->stages);

        // 3. Budgets are populated
        $this->assertSame(300, $plan1->budgets['soft_candidates']);
        $this->assertSame(1000, $plan1->budgets['hard_candidates']);
    }
}
