<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Recommendations\Models\RecoExclusion;
use App\Modules\Recommendations\Models\RecoUserProfile;
use App\Modules\Search\Models\SearchDocument;
use Illuminate\Support\Collection;

class ContentTaxonomyCandidateSource
{
    public function __construct(
        private readonly ReasonCodeGenerator $reasonGenerator
    ) {
    }

    /**
     * Retrieve taxonomy-matched candidates based on user tradition and category interests.
     *
     * @return Collection<int, array{
     *   document: SearchDocument,
     *   score: float,
     *   reason_code: string,
     *   reason_label: string
     * }>
     */
    public function getCandidates(
        ?int $userId = null,
        ?string $category = null,
        ?string $tradition = null,
        int $limit = 10
    ): Collection {
        $excludedIds = [];
        $userTradition = $tradition;

        if ($userId) {
            $excludedIds = RecoExclusion::where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('entity_id')
                ->all();

            if (! $userTradition) {
                $profile = RecoUserProfile::where('user_id', $userId)->first();
                $userTradition = $profile?->tradition;
            }
        }

        $query = SearchDocument::active()
            ->whereNotIn('entity_id', $excludedIds);

        if ($category) {
            $query->where(function ($q) use ($category) {
                $q->where('subtitle', 'LIKE', "%{$category}%")
                    ->orWhere('body', 'LIKE', "%{$category}%");
            });
        }

        $docs = $query->limit($limit)->get();

        return $docs->map(function (SearchDocument $doc) use ($userTradition) {
            $context = [];
            $score = 1.0;

            if ($userTradition && str_contains(mb_strtolower((string) $doc->body), mb_strtolower($userTradition))) {
                $context['same_tradition'] = true;
                $score += 2.0;
            }

            $reasonCode = $this->reasonGenerator->determineReason($context);
            $reasonLabel = $this->reasonGenerator->getLabel($reasonCode);

            return [
                'document' => $doc,
                'score' => $score,
                'reason_code' => $reasonCode,
                'reason_label' => $reasonLabel,
            ];
        });
    }
}

