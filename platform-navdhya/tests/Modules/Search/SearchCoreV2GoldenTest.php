<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Services\SearchScorer;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class SearchCoreV2GoldenTest extends TestCase
{
    use DatabaseTruncation;

    public function test_golden_fixture_ranking_matches(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/Search/ranking-golden.json')), true);
        $this->assertIsArray($fixture);

        $scorer = new SearchScorer($fixture['corpus']);
        foreach ($fixture['queries'] as $q) {
            $ranked = $scorer->rank($q['q'], $q['locale'], 5);
            $this->assertNotEmpty($ranked, "query '{$q['q']}' returned no results");
            $this->assertSame(
                $q['expected_first'],
                $ranked[0]['doc']['id'],
                "query '{$q['q']}' expected {$q['expected_first']} first"
            );
        }
    }
}
