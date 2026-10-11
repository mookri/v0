<?php

namespace App\Modules\Search\Console;

use App\Modules\Search\SearchIndexer;
use Illuminate\Console\Command;

class SearchReindexCommand extends Command
{
    protected $signature = 'search:reindex {type? : Entity type to reindex (seo_page|temple|pandit|puja_service)} {--fresh : Delete all existing documents first}';
    protected $description = 'Rebuild the federated search index';

    public function handle(SearchIndexer $indexer): int
    {
        $type = $this->argument('type');
        $fresh = $this->option('fresh');

        if ($fresh) {
            $this->info('Deleting existing search documents...');
            if ($type) {
                \App\Modules\Search\Models\SearchDocument::where('entity_type', $type)->delete();
            } else {
                \App\Modules\Search\Models\SearchDocument::truncate();
            }
        }

        $this->info('Starting reindex' . ($type ? " for {$type}" : '') . '...');

        $progress = $this->output->createProgressBar(100);
        $progress->start();

        $stats = $indexer->reindex($type);

        $progress->finish();
        $this->newLine(2);

        $this->info("Reindex complete:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total processed', $stats['total']],
                ['Upserted', $stats['upserted']],
                ['Deleted (orphaned)', $stats['deleted']],
            ]
        );

        return self::SUCCESS;
    }
}
