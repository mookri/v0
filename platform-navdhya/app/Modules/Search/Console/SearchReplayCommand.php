<?php

namespace App\Modules\Search\Console;

use App\Modules\Search\Contracts\PanditSearchProvider;
use App\Modules\Search\Contracts\PujaServiceSearchProvider;
use App\Modules\Search\Contracts\SeoPageSearchProvider;
use App\Modules\Search\Contracts\TempleSearchProvider;
use App\Modules\Search\Models\SearchIndexOutbox;
use App\Modules\Search\SearchIndexer;
use Illuminate\Console\Command;

class SearchReplayCommand extends Command
{
    protected $signature = 'search:replay {--tenant= : Replay only specific tenant outbox events}';
    protected $description = 'Replay search transactional outbox events to verify index reconstruction';

    public function handle(SearchIndexer $indexer): int
    {
        $tenantId = $this->option('tenant');

        $query = SearchIndexOutbox::orderBy('created_at');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $events = $query->get();
        $this->info(sprintf('Replaying %d outbox events...', $events->count()));

        $replayed = 0;
        foreach ($events as $record) {
            if ($record->operation === SearchIndexOutbox::OP_DELETE) {
                $indexer->deleteDocument($record->entity_type, $record->entity_id);
            } else {
                $provider = match ($record->entity_type) {
                    'temple' => app(TempleSearchProvider::class),
                    'pandit' => app(PanditSearchProvider::class),
                    'puja_service' => app(PujaServiceSearchProvider::class),
                    'seo_page' => app(SeoPageSearchProvider::class),
                    default => null,
                };

                if ($provider) {
                    $doc = $provider->getDocument($record->entity_id);
                    if ($doc) {
                        $indexer->upsertDocument($doc);
                    } else {
                        $indexer->deleteDocument($record->entity_type, $record->entity_id);
                    }
                }
            }
            $replayed++;
        }

        $this->info("Replay completed: {$replayed} events replayed.");
        return Command::SUCCESS;
    }
}
