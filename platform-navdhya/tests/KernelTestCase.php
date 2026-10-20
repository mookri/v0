<?php

namespace Tests;

use Illuminate\Testing\TestResponse;

/**
 * Base class for kernel/platform tests.
 *
 * Adds the doc 14 §3 helpers as they become applicable. M0 ships the
 * error-envelope assertion; TenantTestCase / actingAsMembership / audit +
 * outbox assertions land with M1-M3 kernel services.
 */
abstract class KernelTestCase extends TestCase
{
    /**
     * Assert a JSON error envelope (doc 06 §4) with the given HTTP status
     * and stable machine-readable `error.code`.
     */
    protected function assertEnvelopeCode(TestResponse $response, int $http, string $code): void
    {
        $response->assertStatus($http);
        $response->assertJsonPath('error.code', $code);
        $response->assertJsonStructure(['error' => ['code', 'message', 'details', 'request_id']]);
    }

    /**
     * Point the module registry at a fixture directory for discovery tests.
     * Returns a fresh, unbooted registry bound to the fixture path.
     */
    protected function registryForFixture(string $fixture): \App\Platform\Modules\ModuleRegistry
    {
        config()->set('platform.modules_path', $this->fixtureModulesPath($fixture));

        return new \App\Platform\Modules\ModuleRegistry(
            $this->app,
            $this->app->make(\App\Platform\Modules\ManifestValidator::class),
        );
    }

    protected function fixtureModulesPath(string $fixture): string
    {
        return base_path('tests/Fixtures/modules/'.$fixture);
    }
}
