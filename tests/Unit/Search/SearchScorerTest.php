<?php

namespace Tests\Unit\Search;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Services\SearchNormalizer;
use App\Modules\Search\Services\SearchScorer;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SearchScorerTest extends TestCase
{
    private function doc(string $id, string $title, array $extra = []): array
    {
        return array_merge([
            'id' => $id,
            'entity_type' => 'puja_service',
            'entity_id' => $id,
            'title' => $title,
            'subtitle' => $extra['subtitle'] ?? null,
            'body' => $extra['body'] ?? '',
            'url_path' => '/services/' . $id,
            'locale' => $extra['locale'] ?? 'en',
            'bookings_30d' => $extra['bookings_30d'] ?? 0,
            'views_7d' => $extra['views_7d'] ?? 0,
            'indexed_at' => $extra['indexed_at'] ?? Carbon::now()->subDays(10),
            'status' => 'ACTIVE',
        ], $extra);
    }

    public function test_empty_query_returns_empty(): void
    {
        $scorer = new SearchScorer([$this->doc('a', 'Ganesh Puja')]);
        $this->assertSame([], $scorer->rank('', 'en', 10));
    }

    public function test_title_match_ranks_first(): void
    {
        $docs = [
            $this->doc('a', 'Ganesh Puja', ['body' => 'ritual for ganesh']),
            $this->doc('b', 'Wedding Ceremony', ['body' => 'includes ganesh puja step']),
        ];
        $scorer = new SearchScorer($docs);
        $ranked = $scorer->rank('ganesh puja', 'en', 10);
        $this->assertNotEmpty($ranked);
        $this->assertSame('a', $ranked[0]['doc']['id']);
    }

    public function test_exact_title_boost_wins(): void
    {
        $docs = [
            $this->doc('a', 'Ganesh Puja'),
            $this->doc('b', 'Ganesh Puja Vidhi and Procedure'),
        ];
        $scorer = new SearchScorer($docs);
        $ranked = $scorer->rank('ganesh puja', 'en', 10);
        $this->assertSame('a', $ranked[0]['doc']['id']);
    }

    public function test_popularity_influences_ordering(): void
    {
        $docs = [
            $this->doc('a', 'Ganesh Puja', ['bookings_30d' => 0, 'views_7d' => 0]),
            $this->doc('b', 'Ganesh Puja', ['bookings_30d' => 100, 'views_7d' => 50]),
        ];
        $scorer = new SearchScorer($docs);
        $ranked = $scorer->rank('ganesh puja', 'en', 10);
        // Both exact title; popularity tiebreaks
        $this->assertSame('b', $ranked[0]['doc']['id']);
    }

    public function test_deterministic_tiebreak_by_title_then_id(): void
    {
        $docs = [
            $this->doc('z', 'Same Title'),
            $this->doc('a', 'Same Title'),
        ];
        $scorer = new SearchScorer($docs);
        $ranked = $scorer->rank('same title', 'en', 10);
        $this->assertSame('a', $ranked[0]['doc']['id']);
        $this->assertSame('z', $ranked[1]['doc']['id']);
    }

    public function test_suggest_prefix_returns_matches(): void
    {
        $docs = [
            $this->doc('a', 'Ganesh Puja'),
            $this->doc('b', 'Griha Pravesh'),
        ];
        $scorer = new SearchScorer($docs);
        $sug = $scorer->suggest('ga', 10);
        $this->assertNotEmpty($sug);
        $ids = array_map(fn ($d) => $d['id'], $sug);
        $this->assertContains('a', $ids);
        $this->assertNotContains('b', $ids);
    }

    public function test_locale_match_boosts(): void
    {
        $docs = [
            $this->doc('a', 'Ganesh Puja', ['locale' => 'en']),
            $this->doc('b', 'Ganesh Puja', ['locale' => 'hi']),
        ];
        $scorer = new SearchScorer($docs);
        $ranked = $scorer->rank('ganesh puja', 'hi', 10);
        $this->assertSame('b', $ranked[0]['doc']['id']);
    }
}