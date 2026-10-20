<?php

namespace Tests\Modules\Pandit;

use App\Platform\Modules\ManifestValidator;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pandit module shape (P9a): the registry — never the provider — mounts the
 * routes, every mounted route keeps the auth.any + tenant gates (the class
 * of bug where a provider-loaded duplicate route won the match and bypassed
 * auth), and each surface carries exactly the manifest's declared gates.
 */
final class PanditModuleTest extends TestCase
{
    /**
     * @return array<string, \Illuminate\Routing\Route>
     */
    private function panditRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name !== null && str_starts_with($name, 'pandit.')) {
                $routes[$name] = $route;
            }
        }

        ksort($routes);

        return $routes;
    }

    private function middlewareFor(string $name): array
    {
        foreach ($this->panditRoutes() as $routeName => $route) {
            if ($routeName === $name) {
                return $route->gatherMiddleware();
            }
        }

        $this->fail("Route {$name} is not mounted.");
    }

    public function test_every_mounted_route_keeps_the_registry_gates(): void
    {
        $routes = $this->panditRoutes();

        $this->assertNotSame([], $routes, 'No pandit.* routes are mounted.');

        foreach ($routes as $name => $route) {
            $middleware = $route->gatherMiddleware();

            // A provider-loaded duplicate would carry neither gate and win
            // the match — that was an auth bypass (P9a).
            $this->assertContains('auth.any', $middleware, "Route {$name} lost auth.any.");
            $this->assertContains('tenant', $middleware, "Route {$name} lost the tenant gate.");
        }

        // The admin application routes were deleted: the lifecycle lives on
        // /api/v1/onboarding and Pandit only reads its own application.
        $names = array_keys($routes);
        $this->assertNotContains('pandit.admin.applications.index', $names);
        $this->assertContains('pandit.application.own', $names);
        $this->assertContains('pandit.apply', $names);
    }

    public function test_surface_gates_match_the_declared_scopes_and_capabilities(): void
    {
        // Browse directory: open to any principal, machine keys scoped.
        foreach (['pandit.profiles.index', 'pandit.profiles.show', 'pandit.profiles.availability'] as $name) {
            $middleware = $this->middlewareFor($name);
            $this->assertContains('scope:pandit:read', $middleware, "Route {$name} must declare scope:pandit:read.");
            $this->assertNotContains('capability:pandit:profile:view', $middleware);
        }

        // Self surface: ownership in the controller, machine scope at the edge.
        foreach (['pandit.profile.own', 'pandit.assignments.own-index', 'pandit.earnings.own'] as $name) {
            $this->assertContains('scope:pandit:self', $this->middlewareFor($name), "Route {$name} must declare scope:pandit:self.");
        }

        // Admin directory: capability only, no step-up on a read.
        $admin = $this->middlewareFor('pandit.admin.profiles.index');
        $this->assertContains('capability:pandit:profile:view', $admin);
        $this->assertNotContains('stepup:2', $admin);

        // Sensitive profile verbs: capability + fresh step-up proof.
        foreach (['pandit.admin.profiles.verify', 'pandit.admin.profiles.suspend', 'pandit.admin.profiles.reactivate'] as $name) {
            $middleware = $this->middlewareFor($name);
            $this->assertContains('capability:pandit:profile:manage', $middleware, "Route {$name} must be capability-gated.");
            $this->assertContains('stepup:2', $middleware, "Route {$name} must demand step-up.");
        }

        // Dispatch: capability-gated, but it is a routine ops action.
        foreach (['pandit.admin.assignments.store', 'pandit.admin.assignments.destroy'] as $name) {
            $middleware = $this->middlewareFor($name);
            $this->assertContains('capability:pandit:assignment:manage', $middleware);
            $this->assertNotContains('stepup:2', $middleware);
        }
    }

    public function test_manifest_matches_the_routes_it_gates(): void
    {
        $path = base_path('app/Modules/Pandit/module.json');
        $this->assertFileExists($path);

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([], (new ManifestValidator())->validate($manifest, 'Pandit'));

        // Only the admin gates are capabilities — self-serve and browse are
        // ownership/scoped, never privileged.
        $this->assertSame([
            'pandit:profile:view',
            'pandit:profile:manage',
            'pandit:assignment:manage',
        ], array_column($manifest['permissions'], 'code'));

        $this->assertSame(
            ['pandit:admin', 'pandit:read', 'pandit:self'],
            collect($manifest['scopes'])->sort()->values()->all(),
        );

        foreach ($manifest['events'] as $event) {
            $this->assertStringStartsWith('pandit.', $event['name']);
        }

        $eventNames = array_column($manifest['events'], 'name');
        foreach ([
            'pandit.application.started',
            'pandit.profile.provisioned',
            'pandit.profile.verified',
            'pandit.assignment.assigned',
            'pandit.availability.updated',
        ] as $expected) {
            $this->assertContains($expected, $eventNames);
        }

        // Application review decisions are not this module's events.
        foreach (['pandit.application.approved', 'pandit.application.rejected'] as $absent) {
            $this->assertNotContains($absent, $eventNames);
        }
    }
}
