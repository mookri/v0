<?php

namespace App\Modules\Search\Console;

use App\Modules\Search\Contracts\PanditSearchProvider;
use App\Modules\Search\Contracts\PujaServiceSearchProvider;
use App\Modules\Search\Contracts\SeoPageSearchProvider;
use App\Modules\Search\Contracts\TempleSearchProvider;
use App\Modules\Search\Models\SearchIndexOutbox;
use App\Modules\Search\SearchIndexer;
use App\Modules\Search\Services\GenerationManager;
use Illuminate\Console\Command;

class SearchIndexWorkCommand extends Command
{
    protected $signature = 'search:index-work
        {--limit=100 : Maximum outbox rows to process per batch}
        {--once : Run a single batch pass and exit}
        {--sleep=2 : Sleep seconds when no pending jobs}';

    protected $description = 'Process transactional search index outbox events';

    public function handle(
        SearchIndexer $indexer,
        GenerationManager $generationManager
    ): int {
        $limit = max((int) $this->option('limit'), 1);
        $once = (bool) $this->option('once');
        $sleep = max((int) $this->option('sleep'), 1);

        $this->info('Starting Search Index Outbox Worker...');

        do {
            $processedCount = $this->processBatch($indexer, $limit);

            if ($processedCount > 0) {
                // Coalesce generation version bump per batch (T-SR04-06 / D-08)
                $generation = $generationManager->advance();
                $this->info(sprintf('Processed %d outbox records. Advanced search generation to %d.', $processedCount, $generation));
            } elseif (! $once) {
                sleep($sleep);
            }
        } while (! $once);

        return Command::SUCCESS;
    }

    public function processBatch(SearchIndexer $indexer, int $limit): int
    {
        $now = now();
        $leaseExpiry = now()->addMinutes(5);

        // Claim records with lease
        $records = SearchIndexOutbox::where(function ($q) use ($now) {
            $q->where('status', SearchIndexOutbox::STATUS_PENDING)
                ->orWhere(function ($q2) use ($now) {
                    $q2->where('status', SearchIndexOutbox::STATUS_PROCESSING)
                        ->where('lease_expires_at', '<', $now);
                });
        })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($records->isEmpty()) {
            return 0;
        }

        $processed = 0;

        foreach ($records as $record) {
            $record->update([
                'status' => SearchIndexOutbox::STATUS_PROCESSING,
                'attempts' => $record->attempts + 1,
                'lease_expires_at' => $leaseExpiry,
            ]);

            try {
                $this->processRecord($record, $indexer);

                $record->update([
                    'status' => SearchIndexOutbox::STATUS_COMPLETED,
                    'lease_expires_at' => null,
                    'error_message' => null,
                ]);

                $processed++;
            } catch (\Throwable $e) {
                $failed = $record->attempts >= 5;
                $record->update([
                    'status' => $failed ? SearchIndexOutbox::STATUS_FAILED : SearchIndexOutbox::STATUS_PENDING,
                    'lease_expires_at' => null,
                    'error_message' => substr($e->getMessage() . "\n" . $e->getTraceAsString(), 0, 2000),
                ]);

                $this->error(sprintf('Error processing outbox record %s: %s', $record->id, $e->getMessage()));
            }
        }

        return $processed;
    }

    private function processRecord(SearchIndexOutbox $record, SearchIndexer $indexer): void
    {
        if ($record->operation === SearchIndexOutbox::OP_DELETE) {
            $indexer->deleteDocument($record->entity_type, $record->entity_id);
            return;
        }

        $provider = match ($record->entity_type) {
            'temple' => app(TempleSearchProvider::class),
            'pandit' => app(PanditSearchProvider::class),
            'puja_service' => app(PujaServiceSearchProvider::class),
            'seo_page' => app(SeoPageSearchProvider::class),
            default => null,
        };

        if ($provider === null) {
            throw new \RuntimeException("Unknown entity type {$record->entity_type}");
        }

        $document = $provider->getDocument($record->entity_id);
        if ($document !== null) {
            $indexer->upsertDocument($document);
        } else {
            $indexer->deleteDocument($record->entity_type, $record->entity_id);
        }
    }
}
