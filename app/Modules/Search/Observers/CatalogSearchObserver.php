<?php

namespace App\Modules\Search\Observers;

use App\Modules\Search\SearchIndexer;
use App\Modules\Catalog\Models\CatalogItem;

class CatalogSearchObserver
{
    public function __construct(
        private readonly SearchIndexer $indexer
    ) {
    }

    public function saved(CatalogItem $item): void
    {
        $document = app(\App\Modules\Search\Contracts\PujaServiceSearchProvider::class)->getDocument($item->id);
        if ($document) {
            $this->indexer->upsertDocument($document);
        } else {
            $this->indexer->deleteDocument('puja_service', $item->id);
        }
    }

    public function deleted(CatalogItem $item): void
    {
        $this->indexer->deleteDocument('puja_service', $item->id);
    }
}
