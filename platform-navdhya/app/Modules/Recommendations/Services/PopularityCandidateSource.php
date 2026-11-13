<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Recommendations\Models\RecoExclusion;
use App\Modules\Search\Models\SearchDocument;
use Illuminate\Support\Collection;

class PopularityCandidateSource
{
    public function __construct(
        private readonly ReasonCodeGenerator $reasonGenerator
    ) {
    }

    /**
     * Retrieve top popular candidates for surface.
     *
     * @return Collection<int, array{
     *   document: SearchDocument,
     *   score: float,
     *   reason_code: string,
     *   reason_label: string
     * }>
     */
    public function getCandidates(
        string $surface,
        ?int $userId = null,
        ?string $city = null,
        ?string $entityType = null,
        int $limit = 10
    ): Collection {
        // Exclusions
        $excludedIds = [];
        if ($userId) {
            $excludedIds = RecoExclusion::where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('entity_id')
                ->all();
        }

        $query = SearchDocument::active()
            ->whereNotIn('entity_id', $excludedIds);

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        if ($city) {
            $query->where(function ($q) use ($city) {
                $q->whereNull('city')->orWhere('city', $city);
            });
        }

        $docs = $query->orderByDesc('bookings_30d')
            ->orderByDesc('views_7d')
            ->limit($limit)
            ->get();

        return $docs->map(function (SearchDocument $doc) use ($city) {
            $context = [];
            if ($city && $doc->city === $city) {
                $context['city_matched'] = true;
            }

            $reasonCode = $this->reasonGenerator->determineReason($context);
            $reasonLabel = $this->reasonGenerator->getLabel($reasonCode);

            $b = (float) $doc->bookings_30d;
            $v = (float) $doc->views_7d;
            $score = round(($b * 1.5) + pow($v, 0.4), 4);

            return [
                'document' => $doc,
                'score' => $score,
                'reason_code' => $reasonCode,
                'reason_label' => $reasonLabel,
            ];
        });
    }
}

