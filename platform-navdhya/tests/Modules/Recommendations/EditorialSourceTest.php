<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Services\EditorialCandidateSource;
use App\Modules\Search\Models\SearchDocument;
use Tests\TenantIntegrationTestCase;

class EditorialSourceTest extends TenantIntegrationTestCase
{
    public function test_editorial_source_returns_festivals_and_special_events(): void
    {
        SearchDocument::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'document_id' => 'editorial_1',
            'entity_type' => 'puja_service',
            'entity_id' => 'diwali_maha_lakshmi_puja',
            'title' => 'Diwali Maha Lakshmi Puja Special',
            'subtitle' => 'Festival of Lights Special Aarti',
            'slug' => 'diwali-maha-lakshmi-puja',
            'url_path' => '/pujas/diwali-maha-lakshmi-puja',
            'city' => 'Varanasi',
            'is_active' => true,
        ]);

        SearchDocument::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'document_id' => 'editorial_2',
            'entity_type' => 'puja_service',
            'entity_id' => 'regular_daily_puja',
            'title' => 'Daily Home Puja',
            'subtitle' => 'Regular',
            'slug' => 'daily-home-puja',
            'url_path' => '/pujas/daily-home-puja',
            'city' => 'Varanasi',
            'is_active' => true,
        ]);

        $source = new EditorialCandidateSource();
        $candidates = $source->getCandidates('Varanasi', 5);

        $this->assertCount(1, $candidates);
        $this->assertSame('diwali_maha_lakshmi_puja', $candidates->first()['document']->entity_id);
        $this->assertSame('FESTIVAL_SPECIAL', $candidates->first()['reason_code']);
    }
}
