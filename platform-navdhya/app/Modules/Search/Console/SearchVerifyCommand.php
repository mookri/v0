<?php

namespace App\Modules\Search\Console;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Search\Models\SearchDictionary;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchPosting;
use App\Modules\Temple\Models\Temple;
use Illuminate\Console\Command;

class SearchVerifyCommand extends Command
{
    protected $signature = 'search:verify';
    protected $description = 'Verify search index integrity and entity parity';

    public function handle(): int
    {
        $this->info('Verifying Search Index Integrity...');

        $discrepancies = 0;

        // 1. Check entity counts
        $templeSource = Temple::where('status', 'ACTIVE')->count();
        $templeIndexed = SearchDocument::where('entity_type', 'temple')->count();

        $panditSource = PanditProfile::where('status', 'VERIFIED')->count();
        $panditIndexed = SearchDocument::where('entity_type', 'pandit')->count();

        $catalogSource = CatalogItem::where('status', 'ACTIVE')->count();
        $catalogIndexed = SearchDocument::where('entity_type', 'puja_service')->count();

        $rows = [
            ['Temple', $templeSource, $templeIndexed, $templeSource === $templeIndexed ? 'MATCH' : 'MISMATCH'],
            ['Pandit', $panditSource, $panditIndexed, $panditSource === $panditIndexed ? 'MATCH' : 'MISMATCH'],
            ['Catalog (Puja Service)', $catalogSource, $catalogIndexed, $catalogSource === $catalogIndexed ? 'MATCH' : 'MISMATCH'],
        ];

        foreach ($rows as $row) {
            if ($row[3] === 'MISMATCH') {
                $discrepancies++;
            }
        }

        $this->table(['Entity Type', 'Source Active', 'Search Indexed', 'Status'], $rows);

        // 2. Check for orphan postings
        $validDocIds = SearchDocument::pluck('id');
        $orphanPostings = SearchPosting::whereNotIn('search_document_id', $validDocIds)->count();
        if ($orphanPostings > 0) {
            $this->warn("Found {$orphanPostings} orphan postings referencing deleted documents.");
            $discrepancies++;
        } else {
            $this->info('Postings check: 0 orphan postings.');
        }

        // 3. Check for stale dictionary terms
        $staleTerms = SearchDictionary::where('doc_freq', '<=', 0)->count();
        if ($staleTerms > 0) {
            $this->warn("Found {$staleTerms} dictionary entries with doc_freq <= 0.");
        } else {
            $this->info('Dictionary check: 0 stale terms.');
        }

        if ($discrepancies > 0) {
            $this->error("Search index verification found {$discrepancies} issue(s).");
            return Command::FAILURE;
        }

        $this->info('Search index verification PASSED.');
        return Command::SUCCESS;
    }
}
