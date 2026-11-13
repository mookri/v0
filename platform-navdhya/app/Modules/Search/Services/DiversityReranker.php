<?php

namespace App\Modules\Search\Services;

class DiversityReranker
{
    /**
     * Rerank candidates enforcing entity type capping and deduplication.
     *
     * @param list<array<string, mixed>> $rankedCandidates List of ['doc' => SearchDocument, 'score' => float, ...]
     * @param int $limit Target limit
     * @param string|null $filterType If entity_type was explicitly requested, capping is bypassed
     * @return list<array<string, mixed>>
     */
    public function rerank(array $rankedCandidates, int $limit, ?string $filterType = null): array
    {
        if (empty($rankedCandidates)) {
            return [];
        }

        // 1. Deduplicate by entity (entity_type + entity_id)
        $seenEntities = [];
        $uniqueCandidates = [];
        foreach ($rankedCandidates as $candidate) {
            $doc = $candidate['doc'] ?? null;
            if ($doc === null) {
                continue;
            }
            $key = $doc->entity_type . ':' . $doc->entity_id;
            if (isset($seenEntities[$key])) {
                continue;
            }
            $seenEntities[$key] = true;
            $uniqueCandidates[] = $candidate;
        }

        // If explicit type was specified, no diversity capping needed
        if ($filterType !== null && $filterType !== '') {
            return array_slice($uniqueCandidates, 0, $limit);
        }

        // 2. Diversity cap: max ceil(limit / 2) slots per entity_type
        $maxPerType = (int) ceil($limit / 2);
        $typeCounts = [];
        $selected = [];

        foreach ($uniqueCandidates as $candidate) {
            $type = (string) ($candidate['doc']->entity_type ?? 'unknown');
            $currentCount = $typeCounts[$type] ?? 0;

            if ($currentCount < $maxPerType) {
                $typeCounts[$type] = $currentCount + 1;
                $selected[] = $candidate;
            }

            if (count($selected) >= $limit) {
                break;
            }
        }

        return $selected;
    }
}
