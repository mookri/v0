<?php

namespace App\Modules\Search\Observers;

use App\Modules\Search\SearchIndexer;
use App\Modules\Temple\Models\Temple;

class TempleSearchObserver
{
    public function __construct(
        private readonly SearchIndexer $indexer
    ) {
    }

    public function saved(Temple $temple): void
    {
        $document = app(\App\Modules\Search\Contracts\TempleSearchProvider::class)->getDocument($temple->id);
        if ($document) {
            $this->indexer->upsertDocument($document);
        } else {
            // Entity doesn't meet criteria (e.g., not ACTIVE), remove from index if present
            $this->indexer->deleteDocument('temple', $temple->id);
        }
    }

    public function deleted(Temple $temple): void
    {
        $this->indexer->deleteDocument('temple', $temple->id);
    }
}
