<?php

namespace App\Modules\Search\Services;

use App\Modules\Search\Models\SearchDelete;
use App\Modules\Search\Models\SearchDictionary;

class FuzzyNeighborhoodMatcher
{
    /**
     * Find fuzzy matches for a token using the deletion neighborhood index.
     *
     * @return array<int, array{term: string, distance: int, popularity: int, doc_freq: int}>
     */
    public function match(
        string $token,
        ?string $tenantId = null,
        string $scheme = 'latin_fold',
        int $maxEdits = 2,
        int $limit = 10
    ): array {
        $tokenLower = mb_strtolower(trim($token), 'UTF-8');
        $len = mb_strlen($tokenLower, 'UTF-8');
        if ($len < 3) {
            return [];
        }

        // Adjust max edits based on length
        $allowedEdits = $len <= 4 ? 1 : min($maxEdits, 2);

        // 1. Generate query deletion forms
        $queryDeletions = [$tokenLower];
        $chars = preg_split('//u', $tokenLower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charCount = count($chars);

        // 1-edit deletions of query
        for ($i = 0; $i < $charCount; $i++) {
            $delChars = $chars;
            array_splice($delChars, $i, 1);
            $form = implode('', $delChars);
            if ($form !== '') {
                $queryDeletions[] = $form;
            }
        }

        // 2-edit deletions of query if length >= 7 and allowedEdits >= 2
        if ($charCount >= 7 && $allowedEdits >= 2) {
            for ($i = 0; $i < $charCount; $i++) {
                for ($j = $i + 1; $j < $charCount; $j++) {
                    $delChars = $chars;
                    array_splice($delChars, $j, 1);
                    array_splice($delChars, $i, 1);
                    $form = implode('', $delChars);
                    if ($form !== '') {
                        $queryDeletions[] = $form;
                    }
                }
            }
        }

        // 2. Query index for candidate original keys
        $candidateKeys = SearchDelete::query()
            ->where('scheme', $scheme)
            ->whereIn('delete_form', $queryDeletions)
            ->distinct()
            ->limit(100)
            ->pluck('original_key')
            ->all();

        // Also check if token matches an indexed delete form directly
        $reverseCandidates = SearchDelete::query()
            ->where('scheme', $scheme)
            ->where('delete_form', $tokenLower)
            ->limit(50)
            ->pluck('original_key')
            ->all();

        $allCandidates = array_unique(array_merge($candidateKeys, $reverseCandidates));
        if (empty($allCandidates)) {
            return [];
        }

        // 3. Verify candidates with bounded edit distance
        $verified = [];
        foreach ($allCandidates as $candidate) {
            $dist = levenshtein($tokenLower, $candidate);
            if ($dist <= $allowedEdits && $dist > 0) {
                $verified[$candidate] = $dist;
            }
        }

        if (empty($verified)) {
            return [];
        }

        // 4. Fetch dictionary metadata for verified candidates
        $dictEntries = SearchDictionary::query()
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->where('scheme', $scheme)
            ->whereIn('term_key', array_keys($verified))
            ->get()
            ->keyBy('term_key');

        $results = [];
        foreach ($verified as $candidate => $dist) {
            $dict = $dictEntries->get($candidate);
            $results[] = [
                'term' => $candidate,
                'distance' => $dist,
                'popularity' => $dict ? (int) $dict->popularity : 0,
                'doc_freq' => $dict ? (int) $dict->doc_freq : 0,
            ];
        }

        // 5. Rank: smallest edit distance first, then highest popularity, then doc_freq
        usort($results, function ($a, $b) {
            if ($a['distance'] !== $b['distance']) {
                return $a['distance'] <=> $b['distance'];
            }
            if ($a['popularity'] !== $b['popularity']) {
                return $b['popularity'] <=> $a['popularity'];
            }
            return $b['doc_freq'] <=> $a['doc_freq'];
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * "Did you mean" suggestion for a multi-token query.
     */
    public function didYouMean(string $query, ?string $tenantId = null, string $scheme = 'latin_fold'): ?string
    {
        $tokens = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (empty($tokens)) {
            return null;
        }

        $correctedTokens = [];
        $hasCorrection = false;

        foreach ($tokens as $token) {
            // Check if exact dictionary match exists
            $exactExists = SearchDictionary::query()
                ->where(function ($q) use ($tenantId) {
                    $q->whereNull('tenant_id');
                    if ($tenantId !== null) {
                        $q->orWhere('tenant_id', $tenantId);
                    }
                })
                ->where('term_key', mb_strtolower($token, 'UTF-8'))
                ->exists();

            if ($exactExists) {
                $correctedTokens[] = $token;
                continue;
            }

            // Fuzzy match using deletion neighborhood
            $matches = $this->match($token, $tenantId, $scheme, 2, 1);
            if (! empty($matches)) {
                $correctedTokens[] = $matches[0]['term'];
                $hasCorrection = true;
            } else {
                $correctedTokens[] = $token;
            }
        }

        if (! $hasCorrection) {
            return null;
        }

        $result = implode(' ', $correctedTokens);
        return $result !== $query ? $result : null;
    }
}
