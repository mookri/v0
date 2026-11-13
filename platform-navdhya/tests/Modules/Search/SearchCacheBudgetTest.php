<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Services\GenerationManager;
use App\Modules\Search\Services\SearchCache;
use Tests\TenantIntegrationTestCase;

class SearchCacheBudgetTest extends TenantIntegrationTestCase
{
    public function test_search_cache_invalidates_immediately_when_generation_advances(): void
    {
        $genManager = new GenerationManager();
        $cache = new SearchCache($genManager);

        $cache->put('term:rudra', ['score' => 1.5]);
        $this->assertNotNull($cache->get('term:rudra'));

        // Advance generation
        $genManager->advance();

        // Cached entry should be invalidated
        $this->assertNull($cache->get('term:rudra'));
        $this->assertSame(0, $cache->count());
    }
}
