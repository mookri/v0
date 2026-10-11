<?php

namespace App\Modules\Search\Observers;

use App\Modules\Search\SearchIndexer;
use App\Modules\Pandit\Models\PanditProfile;

class PanditSearchObserver
{
    public function __construct(
        private readonly SearchIndexer $indexer
    ) {
    }

    public function saved(PanditProfile $pandit): void
    {
        $document = app(\App\Modules\Search\Contracts\PanditSearchProvider::class)->getDocument($pandit->id);
        if ($document) {
            $this->indexer->upsertDocument($document);
        } else {
            $this->indexer->deleteDocument('pandit', $pandit->id);
        }
    }

    public function deleted(PanditProfile $pandit): void
    {
        $this->indexer->deleteDocument('pandit', $pandit->id);
    }
}
