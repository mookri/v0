<?php

namespace App\Modules\Search\Contracts;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Pandit\Models\PanditProfile;
use Illuminate\Support\Str;

class PanditSearchProvider implements ProvidesSearchDocuments
{
    public function getDocuments(): iterable
    {
        return PanditProfile::where('status', PanditProfile::STATUS_ACTIVE)
            ->cursor();
    }

    public function getDocument(string $entityId): ?SearchDocument
    {
        $pandit = PanditProfile::where('id', $entityId)
            ->where('status', PanditProfile::STATUS_ACTIVE)
            ->first();

        return $pandit ? $this->buildDocument($pandit) : null;
    }

    public function getDocumentsSince(\DateTimeInterface $since): iterable
    {
        return PanditProfile::where('status', PanditProfile::STATUS_ACTIVE)
            ->where('updated_at', '>=', $since)
            ->cursor();
    }

    public function entityType(): string
    {
        return 'pandit';
    }

    private function buildDocument(PanditProfile $pandit): SearchDocument
    {
        $specializations = $pandit->specializations ?? [];
        $languages = $pandit->languages ?? [];

        $cardJson = [
            'rating' => $pandit->rating_avg ? (float) $pandit->rating_avg : null,
            'review_count' => $pandit->rating_count,
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
            'city' => null,
            'card_json' => $cardJson,
            'status' => $pandit->status,
            'indexed_at' => now(),
        ]);
    }
}
