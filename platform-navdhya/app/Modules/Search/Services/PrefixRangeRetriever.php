<?php

namespace App\Modules\Search\Services;

use App\Modules\Search\Models\SearchDictionary;
use App\Modules\Search\Models\SearchDocument;
use Illuminate\Database\Eloquent\Collection;

class PrefixRangeRetriever
{
    /**
     * Compute lexicographical upper bound for a prefix string.
     * e.g., "sat" -> "sau", "rudra" -> "rudrb".
     */
    public static function getPrefixUpperBound(string $prefix): string
    {
        $prefixLower = mb_strtolower(trim($prefix), 'UTF-8');
        if ($prefixLower === '') {
            return '';
        }

        $lastChar = mb_substr($prefixLower, -1, 1, 'UTF-8');
        $ord = mb_ord($lastChar, 'UTF-8');
        if ($ord === false) {
            return $prefixLower . "\xFF";
        }

        $nextChar = mb_chr($ord + 1, 'UTF-8');
        if ($nextChar === false) {
            return $prefixLower . "\xFF";
        }

        return mb_substr($prefixLower, 0, -1, 'UTF-8') . $nextChar;
    }

    /**
     * Retrieve matching dictionary terms using index range scan (>= prefix and < prefix_upper).
     *
     * @return Collection<int, SearchDictionary>
     */
    public function retrieve(
        string $prefix,
        ?string $tenantId = null,
        string $locale = 'en',
        string $scheme = 'latin_fold',
        int $limit = 20
    ): Collection {
        $prefixLower = mb_strtolower(trim($prefix), 'UTF-8');
        if ($prefixLower === '') {
            return new Collection();
        }

        $upperBound = self::getPrefixUpperBound($prefixLower);

        return SearchDictionary::query()
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->where('scheme', $scheme)
            ->where('term_key', '>=', $prefixLower)
            ->where('term_key', '<', $upperBound)
            ->orderByDesc('popularity')
            ->orderByDesc('doc_freq')
            ->orderBy('term_key')
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieve search documents associated with dictionary terms matching the prefix.
     *
     * @return Collection<int, SearchDocument>
     */
    public function retrieveDocuments(
        string $prefix,
        ?string $tenantId = null,
        string $locale = 'en',
        string $scheme = 'latin_fold',
        int $limit = 20
    ): Collection {
        $dictionaryEntries = $this->retrieve($prefix, $tenantId, $locale, $scheme, $limit);
        if ($dictionaryEntries->isEmpty()) {
            return new Collection();
        }

        $dictIds = $dictionaryEntries->pluck('id');

        return SearchDocument::active()
            ->whereHas('postings', function ($q) use ($dictIds) {
                $q->whereIn('dictionary_id', $dictIds);
            })
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->limit($limit)
            ->get();
    }
}
