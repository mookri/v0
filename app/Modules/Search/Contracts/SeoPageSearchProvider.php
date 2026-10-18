<?php

namespace App\Modules\Search\Contracts;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\SeoContent\Models\SeoPage;

class SeoPageSearchProvider implements ProvidesSearchDocuments
{
    public function getDocuments(): iterable
    {
        return SeoPage::public()->cursor()->map(fn ($page) => $this->buildDocument($page));
    }

    public function getDocument(string $entityId): ?SearchDocument
    {
        $page = SeoPage::public()->where('slug', $entityId)->first();

        return $page ? $this->buildDocument($page) : null;
    }

    public function getDocumentsSince(\DateTimeInterface $since): iterable
    {
        return SeoPage::public()
            ->where('updated_at', '>=', $since)
            ->cursor()
            ->map(fn ($page) => $this->buildDocument($page));
    }

    public function entityType(): string
    {
        return 'seo_page';
    }

    private function clusterBasePath(\App\Modules\SeoContent\Models\SeoPage $page): ?string
    {
        $cluster = $page->cluster()->first();
        return $cluster instanceof \App\Modules\SeoContent\Models\SeoCluster ? $cluster->base_path : null;
    }

    private function buildDocument(SeoPage $page): SearchDocument
    {
        $pageData = $page->page_json;

        $cardJson = [
            'cluster' => $page->cluster,
            'reading_time' => $pageData['readingTime'] ?? null,
            'proof_points' => $pageData['proofPoints'] ?? [],
        ];

        return new SearchDocument([
            'entity_type' => 'seo_page',
            'entity_id' => $page->slug,
            'tenant_id' => null, // platform-global
            'slug' => $page->slug,
            'url_path' => '/' . ($this->clusterBasePath($page) ?? $page->cluster) . '/' . $page->slug,
            'title' => $page->meta_title ?? $page->title,
            'subtitle' => $page->meta_description,
            'body' => $page->search_text,
            'image_url' => $pageData['heroImage'] ?? null,
            'city' => null,
            'card_json' => $cardJson,
            'status' => $page->noindex ? 'NOINDEX' : 'ACTIVE',
            'indexed_at' => now(),
        ]);
    }
}
