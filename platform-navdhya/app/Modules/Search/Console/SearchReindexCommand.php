<?php

namespace App\Modules\Search\Console;

use App\Modules\Search\SearchIndexer;
use Illuminate\Console\Command;

class SearchReindexCommand extends Command
{
    protected $signature = 'search:reindex {type? : Entity type to reindex (seo_page|temple|pandit|puja_service)} {--fresh : Delete all existing documents first} {--chunk=500 : Chunk size for batched reindex}';
    protected $description = 'Rebuild the federated search index';

    public function handle(SearchIndexer $indexer): int
    {
        $type = $this->argument('type');
        $fresh = $this->option('fresh');
        $chunk = (int) $this->option('chunk');
        if ($chunk < 1) {
            $chunk = 500;
        }

        if ($fresh) {
            $this->info('Deleting existing search documents/terms...');
            if ($type) {
                \App\Modules\Search\Models\SearchDocument::where('entity_type', $type)->delete();
                \App\Modules\Search\Models\SearchTerm::whereHas('document', fn ($q) => $q->where('entity_type', $type))->delete();
            } else {
                \App\Modules\Search\Models\SearchDocument::truncate();
                \App\Modules\Search\Models\SearchTerm::truncate();
            }
        }

        $this->info('Starting reindex' . ($type ? " for {$type}" : '') . " (chunk={$chunk})...");

        $stats = $indexer->reindex($type, $chunk);

        $this->info("Reindex complete:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total processed', $stats['total']],
                ['Upserted', $stats['upserted']],
                ['Deleted (orphaned)', $stats['deleted']],
                ['Terms written', $stats['terms']],
                ['Index version', $stats['version']],
            ]
        );

        return self::SUCCESS;
    }
}
