<?php

namespace App\Modules\Search\Console;

use App\Modules\Search\Services\PatternAggregator;
use Illuminate\Console\Command;

class SearchAggregatePatternsCommand extends Command
{
    protected $signature = 'search:aggregate-patterns
        {--days=1 : Number of days to aggregate}';

    protected $description = 'Aggregate search queries into patterns, locality demand, and popular search terms';

    public function handle(PatternAggregator $aggregator): int
    {
        $days = max((int) $this->option('days'), 1);
        $start = now()->subDays($days);
        $end = now();

        $this->info(sprintf('Aggregating search query patterns from %s to %s...', $start->toDateTimeString(), $end->toDateTimeString()));

        $result = $aggregator->aggregate($start, $end);

        $this->info(sprintf(
            'Aggregation complete. Patterns: %d, Locality demand rows: %d, Popular queries: %d',
            $result['patterns_created'],
            $result['demand_rows'],
            $result['popular_terms_seeded']
        ));

        return Command::SUCCESS;
    }
}

