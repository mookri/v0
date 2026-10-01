<?php

namespace Tests\Platform\Api;

use App\Platform\Api\OpenApiComposer;
use Illuminate\Support\Facades\Artisan;
use Tests\TenantIntegrationTestCase;

/**
 * api:compose (api-platform §9.1): fragments merge without collisions,
 * operationIds stay unique, x-permission codes must be declared, and the
 * discovery route serves the composed artifact.
 */
final class ApiComposeTest extends TenantIntegrationTestCase
{
    public function test_composer_merges_and_validates_pure_arrays(): void
    {
        $kernel = ['components' => ['schemas' => []]];
        $ok = OpenApiComposer::compose(
            $kernel,
            [
                ['source' => 'a.yaml', 'document' => ['paths' => ['/api/v1/a' => ['get' => ['operationId' => 'getA', 'x-permission' => 'platform:a:view']]]]],
                ['source' => 'b.yaml', 'document' => ['paths' => ['/api/v1/b' => ['get' => ['operationId' => 'getB', 'x-gate' => 'ownership']]]]],
            ],
            ['platform:a:view']
        );
        $this->assertSame([], $ok['errors']);
        $this->assertArrayHasKey('/api/v1/a', $ok['document']['paths']);
        $this->assertArrayHasKey('/api/v1/b', $ok['document']['paths']);

        $collision = OpenApiComposer::compose(
            $kernel,
            [
                ['source' => 'a.yaml', 'document' => ['paths' => ['/api/v1/a' => ['get' => ['operationId' => 'getA', 'x-gate' => 'public']]]]],
                ['source' => 'b.yaml', 'document' => ['paths' => ['/api/v1/a' => ['get' => ['operationId' => 'getOther', 'x-gate' => 'public']]]]],
            ],
            []
        );
        $this->assertContains('b.yaml: path collision on /api/v1/a', $collision['errors']);
        $this->assertNull($collision['document']);

        $dupOp = OpenApiComposer::compose(
            $kernel,
            [
                ['source' => 'a.yaml', 'document' => ['paths' => ['/api/v1/a' => ['get' => ['operationId' => 'same', 'x-gate' => 'public']]]]],
                ['source' => 'b.yaml', 'document' => ['paths' => ['/api/v1/b' => ['get' => ['operationId' => 'same', 'x-gate' => 'public']]]]],
            ],
            []
        );
        $this->assertContains('b.yaml: duplicate operationId same', $dupOp['errors']);

        $undeclared = OpenApiComposer::compose(
            $kernel,
            [['source' => 'a.yaml', 'document' => ['paths' => ['/api/v1/a' => ['get' => ['operationId' => 'getA', 'x-permission' => 'ghost:perm']]]]]],
            []
        );
        $this->assertContains('a.yaml: undeclared x-permission ghost:perm', $undeclared['errors']);

        $missing = OpenApiComposer::compose($kernel, [['source' => 'a.yaml', 'document' => ['info' => []]]], []);
        $this->assertContains('a.yaml: missing paths map', $missing['errors']);
    }

    public function test_every_operation_declares_a_gate(): void
    {
        $kernel = ['components' => []];
        $ok = OpenApiComposer::compose(
            $kernel,
            [['source' => 'a.yaml', 'document' => ['paths' => [
                '/api/v1/a' => ['get' => ['operationId' => 'getA', 'x-permission' => 'platform:a:view']],
                '/api/v1/b' => ['post' => ['operationId' => 'doB', 'x-gate' => 'ownership']],
                '/api/v1/c' => ['post' => ['operationId' => 'doC', 'x-gate' => 'public']],
            ]]]],
            ['platform:a:view']
        );
        $this->assertSame([], $ok['errors']);

        $missing = OpenApiComposer::compose(
            $kernel,
            [['source' => 'a.yaml', 'document' => ['paths' => ['/api/v1/a' => ['get' => ['operationId' => 'getA']]]]]],
            []
        );
        $this->assertContains('operation getA (/api/v1/a) declares no x-permission or x-gate', $missing['errors']);
    }

    public function test_command_writes_and_check_passes(): void
    {
        $out = base_path('docs/api/openapi.v1.yaml');
        if (is_file($out)) {
            unlink($out);
        }

        $this->assertSame(0, Artisan::call('api:compose'));
        $this->assertFileExists($out);

        $composed = OpenApiComposer::parseFile($out);
        $this->assertArrayHasKey('/api/v1/platform/tenants', $composed['paths']);
        $this->assertArrayHasKey('/api/v1/identity/profile', $composed['paths']);

        $this->assertSame(0, Artisan::call('api:compose', ['--check' => true]));
    }

    public function test_discovery_serves_composed_spec_to_authenticated_clients(): void
    {
        Artisan::call('api:compose');
        $this->getJson('/api/v1/openapi.json')->assertUnauthorized();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->getJson('/api/v1/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonStructure(['paths', 'components']);
    }
}
