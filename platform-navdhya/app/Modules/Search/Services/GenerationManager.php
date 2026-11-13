<?php

namespace App\Modules\Search\Services;

use App\Modules\Search\Models\SearchMeta;

class GenerationManager
{
    private const VERSION_KEY = 'search_version';

    /**
     * Get current search generation.
     */
    public function current(): int
    {
        $meta = SearchMeta::where('key', self::VERSION_KEY)->first();
        return $meta ? (int) $meta->value : 0;
    }

    /**
     * Advance generation once (coalesced per batch).
     */
    public function advance(): int
    {
        $meta = SearchMeta::where('key', self::VERSION_KEY)->first();
        if (! $meta) {
            SearchMeta::create([
                'key' => self::VERSION_KEY,
                'value' => '1',
                'updated_at' => now(),
            ]);
            return 1;
        }

        $next = ((int) $meta->value) + 1;
        $meta->value = (string) $next;
        $meta->updated_at = now();
        $meta->save();

        return $next;
    }
}
