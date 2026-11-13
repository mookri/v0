<?php

namespace App\Modules\Search\Contracts;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Support\KeyFolder;
use App\Modules\Search\Support\RegionResolver;
use Illuminate\Support\Str;

class PujaServiceSearchProvider implements ProvidesSearchDocuments
{
    public function getDocuments(): iterable
    {
        return CatalogItem::where('status', CatalogItem::STATUS_ACTIVE)
            ->cursor()
            ->map(fn ($item) => $this->buildDocument($item));
    }

    public function getDocument(string $entityId): ?SearchDocument
    {
        $item = CatalogItem::where('id', $entityId)
            ->where('status', CatalogItem::STATUS_ACTIVE)
            ->first();

        return $item ? $this->buildDocument($item) : null;
    }

    public function getDocumentsSince(\DateTimeInterface $since): iterable
    {
        return CatalogItem::where('status', CatalogItem::STATUS_ACTIVE)
            ->where('updated_at', '>=', $since)
            ->cursor()
            ->map(fn ($item) => $this->buildDocument($item));
    }

    public function entityType(): string
    {
        return 'puja_service';
    }

    private function buildDocument(CatalogItem $item): SearchDocument
    {
        $attributes = $item->attributes ?? [];

        $cardJson = [
            'price' => $item->price_minor / 100,
            'currency' => $item->currency,
            'attributes' => $attributes,
            'sku' => $item->sku,
        ];

        $searchText = implode(' ', array_filter([
            $item->name,
            $item->description,
            $item->sku,
            ...array_values($attributes),
        ]));

        $rawCity = $attributes['city'] ?? null;
        $region = RegionResolver::resolve($rawCity);
        $cityName = $region ? $region->name_en : $rawCity;

        return new SearchDocument([
            'entity_type' => 'puja_service',
            'entity_id' => $item->id,
            'tenant_id' => $item->tenant_id,
            'slug' => Str::slug($item->name) . '-' . substr($item->id, -8),
            'url_path' => '/services/' . Str::slug($item->name) . '-' . substr($item->id, -8),
            'title' => $item->name,
            'subtitle' => $attributes['category'] ?? 'Puja Service',
            'body' => $searchText,
            'image_url' => $attributes['image'] ?? null,
            'city' => $cityName,
            'region_id' => $region?->id,
            'card_json' => $cardJson,
            'keys_json' => KeyFolder::generateKeySet($item->name),
            'status' => $item->status,
            'indexed_at' => now(),
            'source_updated_at' => $item->getAttribute('updated_at'),
        ]);
    }
}
