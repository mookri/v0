<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Support\TopKHeap;
use PHPUnit\Framework\TestCase;

class TopKHeapTest extends TestCase
{
    public function test_top_k_heap_maintains_highest_k_scores(): void
    {
        $heap = new TopKHeap(3);

        $scores = [1.2, 5.5, 0.4, 9.8, 3.1, 7.2, 4.0];
        foreach ($scores as $s) {
            $heap->insert(['id' => (string) $s, 'score' => $s]);
        }

        $this->assertSame(3, $heap->count());

        $sorted = $heap->toSortedArray();
        $this->assertCount(3, $sorted);
        $this->assertSame(9.8, $sorted[0]['score']);
        $this->assertSame(7.2, $sorted[1]['score']);
        $this->assertSame(5.5, $sorted[2]['score']);
    }

    public function test_top_k_heap_with_large_input_completes_under_benchmark(): void
    {
        $heap = new TopKHeap(20);

        $start = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $heap->insert(['id' => $i, 'score' => (float) ($i % 73 + ($i * 0.01))]);
        }
        $tookMs = (microtime(true) - $start) * 1000;

        $this->assertLessThan(20.0, $tookMs);
        $this->assertSame(20, $heap->count());
    }
}
