<?php

namespace Tests\Modules\SeoContent;

use App\Platform\Modules\ManifestValidator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class RateLimitingAndManifestTest extends TestCase
{
    public function test_seocontent_module_manifest_is_valid(): void
    {
        $manifestPath = app_path('Modules/SeoContent/module.json');
        $this->assertFileExists($manifestPath);

        $json = json_decode(File::get($manifestPath), true);
        $this->assertIsArray($json);

        $validator = new ManifestValidator();
        $violations = $validator->validate($json, 'SeoContent');

        $this->assertSame([], $violations, 'Manifest should have zero validation violations');
        $this->assertContains('seo_content:pages:view', array_column($json['permissions'], 'code'));
        $this->assertContains('seo_content:read', $json['scopes']);
        $this->assertContains('seo_content.page.published', array_column($json['events'], 'name'));
    }

    public function test_seo_routes_use_named_rate_limiters_not_inline(): void
    {
        $routes = Route::getRoutes();

        $publicPageRoute = $routes->getByName('seo_content.seo.pages.show');
        $this->assertNotNull($publicPageRoute);
        $middleware = $publicPageRoute->gatherMiddleware();
        $this->assertContains('throttle:seo-public', $middleware);
        $this->assertNotContains('throttle:60,1', $middleware);

        $registryRoute = $routes->getByName('seo_content.seo.registry');
        $this->assertNotNull($registryRoute);
        $registryMiddleware = $registryRoute->gatherMiddleware();
        $this->assertContains('throttle:seo-registry', $registryMiddleware);
        $this->assertNotContains('throttle:60,1', $registryMiddleware);
    }
}
