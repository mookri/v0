<?php

namespace App\Modules\Search\Contracts;

use App\Modules\Feedback\Contracts\ReadsFeedbackQuality;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Support\KeyFolder;
use App\Modules\Search\Support\RegionResolver;
use Illuminate\Support\Str;

class PanditSearchProvider implements ProvidesSearchDocuments
{
    public function __construct(
        private readonly ?ReadsFeedbackQuality $feedbackQuality = null,
    ) {
    }
    public function getDocuments(): iterable
    {
        return PanditProfile::withoutGlobalScopes()
            ->where('status', PanditProfile::STATUS_ACTIVE)
            ->cursor()
            ->map(fn ($pandit) => $this->buildDocument($pandit));
    }

    public function getDocument(string $entityId): ?SearchDocument
    {
        $pandit = PanditProfile::withoutGlobalScopes()
            ->where('id', $entityId)
            ->where('status', PanditProfile::STATUS_ACTIVE)
            ->first();

        return $pandit ? $this->buildDocument($pandit) : null;
    }

    public function getDocumentsSince(\DateTimeInterface $since): iterable
    {
        return PanditProfile::withoutGlobalScopes()
            ->where('status', PanditProfile::STATUS_ACTIVE)
            ->where('updated_at', '>=', $since)
            ->cursor()
            ->map(fn ($pandit) => $this->buildDocument($pandit));
    }

    public function entityType(): string
    {
        return 'pandit';
    }

    private function buildDocument(PanditProfile $pandit): SearchDocument
    {
        $specializations = $pandit->specializations ?? [];
        $languages = $pandit->languages ?? [];

        $qualitySummary = null;
        if ($this->feedbackQuality !== null) {
            try {
                $qualitySummary = $this->feedbackQuality->summaryForSubject('pandit', (string) $pandit->id);
            } catch (\Throwable) {
                $qualitySummary = null;
            }
        }

        $hasFeedbackSamples = $qualitySummary !== null && $qualitySummary['sample_count'] > 0;
        $reviewCount = $hasFeedbackSamples ? $qualitySummary['sample_count'] : $pandit->rating_count;

        $cardJson = [
            'rating' => $hasFeedbackSamples
                ? $qualitySummary['bayesian_average']
                : ($pandit->rating_avg ? (float) $pandit->rating_avg : null),
            'review_count' => $reviewCount,
            'confidence_tier' => $hasFeedbackSamples
                ? $qualitySummary['confidence_tier']
                : ($reviewCount === 0 ? 'NO_REVIEWS' : ($reviewCount < 5 ? 'NEW_PROVIDER' : 'ESTABLISHED_SAMPLE')),
            'confidence_label' => $hasFeedbackSamples
                ? $qualitySummary['confidence_label']
                : ($reviewCount === 0 ? 'No reviews yet' : ($reviewCount < 5 ? 'New provider' : 'Established sample')),
            'punctuality_wilson_lower' => $hasFeedbackSamples ? $qualitySummary['punctuality_wilson_lower'] : null,
            'total_bookings' => $pandit->total_bookings,
            'experience_years' => $pandit->experience_years,
            'specializations' => $specializations,
            'languages' => $languages,
            'from_price' => $pandit->base_rate_minor ? $pandit->base_rate_minor / 100 : null,
            'currency' => $pandit->currency,
            'verification_status' => $pandit->verification_status,
        ];

        $searchText = implode(' ', array_filter([
            $pandit->display_name,
            $pandit->bio,
            ...$specializations,
            ...$languages,
        ]));

        $primaryArea = null;
        try {
            $primaryArea = $pandit->serviceAreas()->where('is_active', true)->orderByDesc('is_primary')->first();
        } catch (\Throwable) {
            // fail-open
        }

        $region = $primaryArea ? RegionResolver::resolve($primaryArea->region_name) : null;
        $cityName = $region ? $region->name_en : ($primaryArea ? $primaryArea->region_name : null);

        return new SearchDocument([
            'entity_type' => 'pandit',
            'entity_id' => $pandit->id,
            'tenant_id' => $pandit->tenant_id,
            'slug' => Str::slug($pandit->display_name) . '-' . substr($pandit->id, -8),
            'url_path' => '/pandits/' . Str::slug($pandit->display_name) . '-' . substr($pandit->id, -8),
            'title' => $pandit->display_name,
            'subtitle' => implode(', ', array_merge(
                array_slice($specializations, 0, 2),
                array_slice($languages, 0, 1)
            )) ?: 'Verified Pandit',
            'body' => $searchText,
            'image_url' => null,
            'city' => $cityName,
            'region_id' => $region?->id,
            'card_json' => $cardJson,
            'keys_json' => KeyFolder::generateKeySet($pandit->display_name),
            'status' => $pandit->status,
            'indexed_at' => now(),
            'source_updated_at' => $pandit->getAttribute('updated_at'),
        ]);
    }
}
