<?php

namespace App\Modules\Search;

use App\Modules\Search\Contracts\ProvidesSearchDocuments;
use App\Modules\Search\Models\SearchDocument;

class SearchIndexer
{
    public function reindex(string $entityType = null): array
    {
        $providers = $this->resolveProviders($entityType);
        $stats = ['total' => 0, 'upserted' => 0, 'deleted' => 0];

        foreach ($providers as $provider) {
            $result = $this->reindexProvider($provider);
            $stats['total'] += $result['total'];
            $stats['upserted'] += $result['upserted'];
            $stats['deleted'] += $result['deleted'];
        }

        return $stats;
    }

    public function upsertDocument(SearchDocument $document): void
    {
        SearchDocument::updateOrCreate(
            ['entity_type' => $document->entity_type, 'entity_id' => $document->entity_id],
            $document->toArray()
        );
    }

    public function deleteDocument(string $entityType, string $entityId): void
    {
        SearchDocument::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->delete();
    }

    public function deleteByTenant(string $tenantId): void
    {
        SearchDocument::where('tenant_id', $tenantId)->delete();
    }

    public function getIndexedCount(): int
    {
        return SearchDocument::count();
    }

    private function resolveProviders(?string $entityType): array
    {
        $allProviders = [
            'seo_page' => app(ProvidesSearchDocuments::class . ':seo_page'),
            'temple' => app(ProvidesSearchDocuments::class . ':temple'),
            'pandit' => app(ProvidesSearchDocuments::class . ':pandit'),
            'puja_service' => app(ProvidesSearchDocuments::class . ':puja_service'),
        ];

        if ($entityType) {
            return isset($allProviders[$entityType]) ? [$allProviders[$entityType]] : [];
        }

        return $allProviders;
    }

    private function reindexProvider(ProvidesSearchDocuments $provider): array
    {
        $upserted = 0;
        $total = 0;

        foreach ($provider->getDocuments() as $document) {
            $total++;
            $this->upsertDocument($document);
            $upserted++;
        }

        // Delete orphaned documents (entities no longer returned by provider)
        $existingIds = SearchDocument::where('entity_type', $provider->entityType())
            ->pluck('entity_id')
            ->toArray();

        $currentIds = [];
        foreach ($provider->getDocuments() as $doc) {
            $currentIds[] = $doc->entity_id;
        }

        $orphaned = array_diff($existingIds, $currentIds);
        $deleted = 0;

        if ($orphaned) {
            SearchDocument::where('entity_type', $provider->entityType())
                ->whereIn('entity_id', $orphaned)
                ->delete();
            $deleted = count($orphaned);
        }

        return [
            'total' => $total,
            'upserted' => $upserted,
            'deleted' => $deleted,
        ];
    }
}
