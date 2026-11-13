<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Services\PrefixSpanMiner;
use Tests\TestCase;

class PrefixSpanTest extends TestCase
{
    public function test_prefix_span_mines_sequential_vedic_sanskars(): void
    {
        $sequences = [
            // User 1 sequence
            ['vivah_sanskar', 'garbhadhana', 'simantonayana', 'namakarana'],
            // User 2 sequence
            ['vivah_sanskar', 'garbhadhana', 'simantonayana', 'namakarana'],
            // User 3 sequence
            ['vivah_sanskar', 'simantonayana', 'namakarana'],
            // User 4 sequence
            ['griha_pravesh', 'satyanarayan'],
            // User 5 sequence
            ['vivah_sanskar', 'garbhadhana', 'namakarana'],
        ];

        $miner = new PrefixSpanMiner();
        $patterns = $miner->mine($sequences, 0.40, 4);

        $this->assertNotEmpty($patterns);

        // Pattern ['vivah_sanskar', 'simantonayana', 'namakarana'] appears in users 1, 2, 3 (support 3/5 = 0.60)
        $hasSequentialSanskar = false;
        foreach ($patterns as $p) {
            if ($p['pattern'] === ['vivah_sanskar', 'simantonayana', 'namakarana']) {
                $hasSequentialSanskar = true;
                $this->assertGreaterThanOrEqual(0.60, $p['support']);
                break;
            }
        }

        $this->assertTrue($hasSequentialSanskar, 'Expected sequential sanskar pattern was discovered.');
    }

    public function test_prefix_span_empty_sequences_returns_empty(): void
    {
        $miner = new PrefixSpanMiner();
        $this->assertSame([], $miner->mine([]));
    }
}

