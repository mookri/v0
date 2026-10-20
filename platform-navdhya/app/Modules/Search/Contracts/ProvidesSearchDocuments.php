<?php

namespace App\Modules\Search\Contracts;

use App\Modules\Search\Models\SearchDocument;

/**
 * Contract for modules to provide their search documents.
 * Each module implements this to project its entities into the federated index.
 */
interface ProvidesSearchDocuments
{
    /**
     * Get all documents for full reindex.
     *
     * @return iterable<SearchDocument>
     */
    public function getDocuments(): iterable;

    /**
     * Get a single document by entity ID.
     */
    public function getDocument(string $entityId): ?SearchDocument;

    /**
     * Get documents modified since a given timestamp (for incremental sync).
     *
     * @return iterable<SearchDocument>
     */
    public function getDocumentsSince(\DateTimeInterface $since): iterable;

    /**
     * The entity type this provider handles (seo_page | temple | pandit | puja_service).
     */
    public function entityType(): string;
}
