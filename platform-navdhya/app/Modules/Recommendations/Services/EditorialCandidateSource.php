<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Search\Models\SearchDocument;
use Illuminate\Support\Collection;

class EditorialCandidateSource
{
    /**
     * Retrieve curated seasonal or festival candidates.
     *
     * @return Collection<int, array{
     *   document: SearchDocument,
     *   score: float,
     *   reason_code: string,
     *   reason_label: string
     * }>
     */
    public function getCandidates(?string $city = null, int $limit = 5): Collection
    {
        $query = SearchDocument::active()
            ->where(function ($q) {
                $q->where('title', 'LIKE', '%Special%')
                    ->orWhere('title', 'LIKE', '%Maha%')
                    ->orWhere('title', 'LIKE', '%Aarti%');
            });

        if ($city) {
            $query->where(function ($q) use ($city) {
                $q->whereNull('city')->orWhere('city', $city);
            });
        }

        $docs = $query->limit($limit)->get();

        return $docs->map(fn (SearchDocument $doc) => [
            'document' => $doc,
            'score' => 2.50,
            'reason_code' => 'FESTIVAL_SPECIAL',
            'reason_label' => 'Sacred seasonal ritual special',
        ]);
    }
}

