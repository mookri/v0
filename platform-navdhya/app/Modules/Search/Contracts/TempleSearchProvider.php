<?php

namespace App\Modules\Search\Contracts;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Support\KeyFolder;
use App\Modules\Search\Support\RegionResolver;
use App\Modules\Temple\Models\Temple;

class TempleSearchProvider implements ProvidesSearchDocuments
{
    public function getDocuments(): iterable
    {
        return Temple::where('status', Temple::STATUS_ACTIVE)
            ->cursor()
            ->map(fn ($temple) => $this->buildDocument($temple));
    }

    public function getDocument(string $entityId): ?SearchDocument
    {
        $temple = Temple::where('id', $entityId)
            ->where('status', Temple::STATUS_ACTIVE)
            ->first();

        return $temple ? $this->buildDocument($temple) : null;
    }

    public function getDocumentsSince(\DateTimeInterface $since): iterable
    {
        return Temple::where('status', Temple::STATUS_ACTIVE)
            ->where('updated_at', '>=', $since)
            ->cursor()
            ->map(fn ($temple) => $this->buildDocument($temple));
    }

    public function entityType(): string
    {
        return 'temple';
    }

    private function buildDocument(Temple $temple): SearchDocument
    {
        $amenities = $temple->amenities ?? [];
        $images = $temple->images ?? [];

        $cardJson = [
            'city' => $temple->city,
            'state' => $temple->state,
            'verified' => $temple->verification_status === Temple::VERIFICATION_VERIFIED,
            'amenities' => $amenities,
            'type' => $temple->type,
            'trust_name' => $temple->trust_name,
        ];

        $searchText = implode(' ', array_filter([
            $temple->name,
            $temple->trust_name,
            $temple->city,
            $temple->state,
            $temple->address,
            $temple->description,
            ...$amenities,
        ]));

        $region = RegionResolver::resolve($temple->city);
        $cityName = $region ? $region->name_en : $temple->city;

        return new SearchDocument([
            'entity_type' => 'temple',
            'entity_id' => $temple->id,
            'tenant_id' => $temple->tenant_id,
            'slug' => $temple->slug,
            'url_path' => '/temples/' . $temple->slug,
            'title' => $temple->name,
            'subtitle' => $temple->trust_name . ' • ' . $temple->city . ', ' . $temple->state,
            'body' => $searchText,
            'image_url' => $images[0] ?? null,
            'city' => $cityName,
            'region_id' => $region?->id,
            'card_json' => $cardJson,
            'keys_json' => KeyFolder::generateKeySet($temple->name),
            'status' => $temple->status,
            'indexed_at' => now(),
            'source_updated_at' => $temple->updated_at,
        ]);
    }
}
