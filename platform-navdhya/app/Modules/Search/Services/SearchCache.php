<?php

namespace App\Modules\Search\Services;

class SearchCache
{
    private const MAX_ENTRIES = 5000;

    /** @var array<string, mixed> */
    private array $store = [];
    private int $cachedGeneration = -1;

    public function __construct(
        private readonly ?GenerationManager $generationManager = null
    ) {
    }

    private function generationManager(): GenerationManager
    {
        return $this->generationManager ?? new GenerationManager();
    }

    /**
     * Get cached value if generation matches; null otherwise.
     */
    public function get(string $key): mixed
    {
        $currentGen = $this->generationManager()->current();
        if ($this->cachedGeneration !== $currentGen) {
            $this->flush();
            $this->cachedGeneration = $currentGen;
            return null;
        }

        return $this->store[$key] ?? null;
    }

    /**
     * Put value into bounded in-memory cache.
     */
    public function put(string $key, mixed $value): void
    {
        $currentGen = $this->generationManager()->current();
        if ($this->cachedGeneration !== $currentGen) {
            $this->flush();
            $this->cachedGeneration = $currentGen;
        }

        // Enforce hard capacity bound
        if (count($this->store) >= self::MAX_ENTRIES) {
            // Evict oldest entry (first key)
            $firstKey = array_key_first($this->store);
            if ($firstKey !== null) {
                unset($this->store[$firstKey]);
            }
        }

        $this->store[$key] = $value;
    }

    /**
     * Invalidate entire in-memory store.
     */
    public function flush(): void
    {
        $this->store = [];
    }

    public function count(): int
    {
        return count($this->store);
    }
}
