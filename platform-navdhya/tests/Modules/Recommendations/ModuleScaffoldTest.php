<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Services\ContentTaxonomyCandidateSource;
use App\Modules\Recommendations\Services\PopularityCandidateSource;
use App\Modules\Recommendations\Services\ReasonCodeGenerator;
use App\Platform\Modules\ModuleRegistry;
use Tests\TenantIntegrationTestCase;

class ModuleScaffoldTest extends TenantIntegrationTestCase
{
    public function test_recommendations_module_is_active_and_services_registered(): void
    {
        $registry = app(ModuleRegistry::class);
        $module = $registry->find('recommendations');

        $this->assertNotNull($module);
        $this->assertSame('Recommendations', $module->name);

        // Assert IoC singletons are bound
        $this->assertInstanceOf(ReasonCodeGenerator::class, app(ReasonCodeGenerator::class));
        $this->assertInstanceOf(PopularityCandidateSource::class, app(PopularityCandidateSource::class));
        $this->assertInstanceOf(ContentTaxonomyCandidateSource::class, app(ContentTaxonomyCandidateSource::class));
    }
}
