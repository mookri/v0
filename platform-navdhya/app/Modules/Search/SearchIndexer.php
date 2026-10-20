<?php

namespace App\Modules\Search;

use App\Modules\Search\Contracts\ProvidesSearchDocuments;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchMeta;
use App\Modules\Search\Models\SearchTerm;
use App\Modules\Search\Services\SearchNormalizer;
use Illuminate\Support\Str;

class SearchIndexer
{
    public function __construct(
        private readonly ?SearchNormalizer $normalizer = null,
    ) {
    }

    private function normalizer(): SearchNormalizer
    {
        return $this->normalizer ?? new SearchNormalizer();
    }

    /**
     * @return array{total:int,upserted:int,deleted:int,terms:int,version:int}
     */
    public function reindex(?string $entityType = null, int $chunk = 500): array
    {
        $providers = $this->resolveProviders($entityType);
        $stats = ['total' => 0, 'upserted' => 0, 'deleted' => 0, 'terms' => 0];

        foreach ($providers as $provider) {
            $result = $this->reindexProvider($provider, $chunk);
            $stats['total'] += $result['total'];
            $stats['upserted'] += $result['upserted'];
            $stats['deleted'] += $result['deleted'];
            $stats['terms'] += $result['terms'];
        }

        $version = $this->bumpVersion();

        return array_merge($stats, ['version' => $version]);
    }

    public function upsertDocument(SearchDocument $document): void
    {
        $persisted = SearchDocument::updateOrCreate(
            ['entity_type' => $document->entity_type, 'entity_id' => $document->entity_id],
            $document->toArray()
        );

        $this->rebuildTermsForDocument($persisted);
        $this->bumpVersion();
    }

    public function deleteDocument(string $entityType, string $entityId): void
    {
        $doc = SearchDocument::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        if ($doc) {
            SearchTerm::where('search_document_id', $doc->id)->delete();
            $doc->delete();
            $this->bumpVersion();
        }
    }

    public function deleteByTenant(string $tenantId): void
    {
        $ids = SearchDocument::where('tenant_id', $tenantId)->pluck('id');
        SearchTerm::whereIn('search_document_id', $ids)->delete();
        SearchDocument::where('tenant_id', $tenantId)->delete();
        $this->bumpVersion();
    }

    public function getIndexedCount(): int
    {
        return SearchDocument::count();
    }

    public function getVersion(): int
    {
        $row = SearchMeta::where('key', 'search_version')->first();
        return $row ? (int) $row->value : 0;
    }

    public function bumpVersion(): int
    {
        $row = SearchMeta::where('key', 'search_version')->first();
        if (! $row) {
            SearchMeta::create(['key' => 'search_version', 'value' => '1', 'updated_at' => now()]);
            return 1;
        }
        $new = ((int) $row->value) + 1;
        $row->value = (string) $new;
        $row->updated_at = now();
        $row->save();
        return $new;
    }

    /** @return array<int,ProvidesSearchDocuments> */
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

        return array_values($allProviders);
    }

    /** @return array{total:int,upserted:int,deleted:int,terms:int} */
    private function reindexProvider(ProvidesSearchDocuments $provider, int $chunk): array
    {
        $total = 0;
        $upserted = 0;
        $deleted = 0;
        $terms = 0;

        // Collect current ids for orphan detection.
        $currentIds = [];

        $buffer = [];
        foreach ($provider->getDocuments() as $document) {
            $buffer[] = $document;
            if (count($buffer) >= $chunk) {
                foreach ($buffer as $doc) {
                    $total++;
                    $this->upsertDocument($doc);
                    $upserted++;
                    $currentIds[] = $doc->entity_id;
                    $terms += $this->countTermsFor($doc);
                }
                $buffer = [];
            }
        }
        foreach ($buffer as $doc) {
            $total++;
            $this->upsertDocument($doc);
            $upserted++;
            $currentIds[] = $doc->entity_id;
            $terms += $this->countTermsFor($doc);
        }

        // Orphan deletion.
        $existingIds = SearchDocument::where('entity_type', $provider->entityType())
            ->pluck('entity_id')
            ->toArray();

        $orphaned = array_diff($existingIds, $currentIds);
        if ($orphaned) {
            $orphanDocs = SearchDocument::where('entity_type', $provider->entityType())
                ->whereIn('entity_id', $orphaned)
                ->get();
            foreach ($orphanDocs as $od) {
                SearchTerm::where('search_document_id', $od->id)->delete();
                $od->delete();
                $deleted++;
            }
        }

        // Rebuild terms for this type from scratch to avoid stale postings.
        // (upsert already wrote terms; orphan cleanup done above.)

        return [
            'total' => $total,
            'upserted' => $upserted,
            'deleted' => $deleted,
            'terms' => $terms,
        ];
    }

    private function rebuildTermsForDocument(SearchDocument $doc): void
    {
        SearchTerm::where('search_document_id', $doc->id)->delete();

        $normalizer = $this->normalizer();
        $text = implode(' ', array_filter([
            (string) $doc->title,
            (string) $doc->subtitle,
            (string) $doc->body,
        ]));
        $tokens = $normalizer->tokenize($text, (string) ($doc->locale ?? 'en'));
        $tokens = array_values(array_unique($tokens));

        $weight = 1.0 + (0.1 * min(10, (int) ($doc->bookings_30d ?? 0)));
        $rows = [];
        foreach ($tokens as $t) {
            $rows[] = [
                'id' => (string) Str::ulid(),
                'term' => $t,
                'normalized' => mb_strtolower($t, 'UTF-8'),
                'kind' => 'entity_title',
                'weight' => $weight,
                'search_document_id' => $doc->id,
                'title' => $doc->title,
                'url' => $doc->url_path,
                'city' => $doc->city,
                'tenant_id' => $doc->tenant_id,
                'locale' => $doc->locale ?? 'en',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            SearchTerm::insert($rows);
        }
    }

    private function countTermsFor(SearchDocument $doc): int
    {
        return SearchTerm::where('search_document_id', $doc->id)->count();
    }
}