<?php

namespace App\Modules\Search\Support;

/**
 * Bounded Min-Heap maintaining the Top-K elements by score in O(N log K) time.
 */
class TopKHeap
{
    /** @var list<array<string, mixed>> */
    private array $heap = [];

    public function __construct(
        public readonly int $capacity
    ) {
    }

    public function count(): int
    {
        return count($this->heap);
    }

    public function isEmpty(): bool
    {
        return empty($this->heap);
    }

    /**
     * Insert candidate if it qualifies for top-K.
     *
     * @param array<string, mixed> $candidate Must contain float 'score'
     */
    public function insert(array $candidate): void
    {
        if ($this->capacity <= 0) {
            return;
        }

        if (count($this->heap) < $this->capacity) {
            $this->heap[] = $candidate;
            $this->siftUp(count($this->heap) - 1);
            return;
        }

        // Compare against current minimum (root)
        if (($candidate['score'] ?? 0.0) > ($this->heap[0]['score'] ?? 0.0)) {
            $this->heap[0] = $candidate;
            $this->siftDown(0);
        }
    }

    /**
     * Return all items sorted descending by score.
     *
     * @return list<array<string, mixed>>
     */
    public function toSortedArray(): array
    {
        $items = $this->heap;
        usort($items, function (array $a, array $b) {
            $sa = (float) ($a['score'] ?? 0.0);
            $sb = (float) ($b['score'] ?? 0.0);
            if ($sa !== $sb) {
                return $sb <=> $sa; // Descending
            }
            $ta = (string) ($a['doc']->title ?? '');
            $tb = (string) ($b['doc']->title ?? '');
            return $ta <=> $tb;
        });

        return $items;
    }

    private function siftUp(int $index): void
    {
        while ($index > 0) {
            $parent = (int) floor(($index - 1) / 2);
            if (($this->heap[$index]['score'] ?? 0.0) < ($this->heap[$parent]['score'] ?? 0.0)) {
                $temp = $this->heap[$index];
                $this->heap[$index] = $this->heap[$parent];
                $this->heap[$parent] = $temp;
                $index = $parent;
            } else {
                break;
            }
        }
    }

    private function siftDown(int $index): void
    {
        $count = count($this->heap);
        while (true) {
            $smallest = $index;
            $left = 2 * $index + 1;
            $right = 2 * $index + 2;

            if ($left < $count && ($this->heap[$left]['score'] ?? 0.0) < ($this->heap[$smallest]['score'] ?? 0.0)) {
                $smallest = $left;
            }

            if ($right < $count && ($this->heap[$right]['score'] ?? 0.0) < ($this->heap[$smallest]['score'] ?? 0.0)) {
                $smallest = $right;
            }

            if ($smallest !== $index) {
                $temp = $this->heap[$index];
                $this->heap[$index] = $this->heap[$smallest];
                $this->heap[$smallest] = $temp;
                $index = $smallest;
            } else {
                break;
            }
        }
    }
}
