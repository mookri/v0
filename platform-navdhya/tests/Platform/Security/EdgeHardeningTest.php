<?php

namespace Tests\Platform\Security;

use App\Models\User;
use App\Modules\Search\Models\SearchDocument;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Tests\TenantIntegrationTestCase;

/**
 * Regression cover for the backend hardening wave 2 (BACKEND-RCA
 * 2026-10-21, findings 2.15-2.23).
 */
final class EdgeHardeningTest extends TenantIntegrationTestCase
{
    protected function tearDown(): void
    {
        // The trusted-host list is static state on the Request class:
        // without this, a test that configures it would reject the
        // Host of every later request in the same process.
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    /**
     * A forged Host header must be rejected when the host is resolved:
     * absolute URLs (the password-reset link) are built from it, so a
     * host that is not trusted leaves the reset token deliverable to
     * the attacker.
     */
    public function test_an_untrusted_host_is_rejected(): void
    {
        Request::setTrustedHosts(['navdhya.test']);

        $request = Request::create('http://evil.test/up');

        $this->expectException(SuspiciousOperationException::class);
        $request->getHost();
    }

    /**
     * A trusted host resolves normally.
     */
    public function test_a_trusted_host_resolves(): void
    {
        Request::setTrustedHosts(['navdhya.test']);

        $request = Request::create('http://navdhya.test/up');

        $this->assertSame('navdhya.test', $request->getHost());
    }

    /**
     * The whole /api/v1 surface must carry a rate limit. The limiter
     * used to live in a provider that was never registered, and
     * `throttle.tier` only covers machine clients - so a human session
     * could loop every state-changing route at full speed.
     */
    public function test_the_api_group_carries_a_rate_limit(): void
    {
        // The api GROUP carries the limit: a Route object only holds
        // its own middleware, not the group's.
        $kernel = app(Kernel::class);
        $groups = method_exists($kernel, 'getMiddlewareGroups') ? $kernel->getMiddlewareGroups() : [];
        $apiGroup = $groups['api'] ?? [];

        $throttled = false;

        foreach ($apiGroup as $middleware) {
            if (is_string($middleware) && str_contains($middleware, 'throttle')) {
                $throttled = true;

                break;
            }
        }

        $this->assertTrue($throttled, 'The api middleware group must carry a rate limit.');
        $this->assertNotNull(
            RateLimiter::limiter('api'),
            'The "api" limiter must be registered, or throttle:api throws at dispatch.'
        );
    }

    /**
     * The limit is actually enforced on the API surface.
     */
    public function test_the_api_surface_answers_429_after_the_limit(): void
    {
        $last = null;

        for ($i = 0; $i < 121; $i++) {
            $last = $this->getJson('/api/v1/platform/tenants');
        }

        $this->assertNotNull($last);
        $this->assertSame(429, $last->getStatusCode(), 'The API surface must rate-limit.');
    }

    /**
     * The platform ops surfaces resolve the tenant from the header and
     * must not then read a DIFFERENT tenant from the query string.
     */
    public function test_ops_surfaces_reject_a_foreign_tenant_query_parameter(): void
    {
        $tenant = $this->makeTenant();
        $other = $this->makeTenant();

        $user = User::factory()->create();
        $membership = $this->makeMembership($tenant, $user);
        $this->grantCapabilities($tenant, $membership, ['platform:ops:view']);

        [$name, $value] = $this->loginSessionFor($user);

        $this->withUnencryptedCookie($name, $value)
            ->getJson(
                '/api/v1/platform/stats?tenant_id='.(string) $other->getKey(),
                $this->tenantHeaders($tenant),
            )
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'tenant_not_in_token');
    }

    /**
     * The public search surfaces drop the tenant middleware, so the
     * tenant header must be validated and the entity lookup must carry
     * a tenant predicate.
     */
    public function test_public_search_does_not_read_an_unknown_tenant(): void
    {
        $tenant = $this->makeTenant();

        $this->searchDocument('foreign-puja', 'Foreign tenant puja', (string) $tenant->getKey());

        // A tenant id that does not exist is not honoured: only a real,
        // active tenant may be read.
        $this->getJson('/api/v1/search/entities/puja_service/foreign-puja', [
            'X-Tenant-Id' => '01J0000000000000000000000',
        ])->assertNotFound();
    }

    /**
     * The entity endpoint must not read a tenant it was not given.
     */
    public function test_public_search_does_not_read_a_foreign_tenant(): void
    {
        $tenant = $this->makeTenant();
        $other = $this->makeTenant();

        $this->searchDocument('foreign-puja', 'Foreign tenant puja', (string) $other->getKey());

        $this->getJson('/api/v1/search/entities/puja_service/foreign-puja', $this->tenantHeaders($tenant))
            ->assertNotFound();
    }

    /**
     * The public search may read the tenant it is given.
     */
    public function test_public_search_reads_an_active_tenant(): void
    {
        $tenant = $this->makeTenant();

        $this->searchDocument('own-puja', 'Own tenant puja', (string) $tenant->getKey());

        $this->getJson('/api/v1/search/entities/puja_service/own-puja', $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonPath('data.title', 'Own tenant puja');
    }

    private function searchDocument(string $slug, string $title, ?string $tenantId): void
    {
        SearchDocument::query()->create([
            'entity_type' => 'puja_service',
            'entity_id' => $slug,
            'tenant_id' => $tenantId,
            'slug' => $slug,
            'url_path' => '/puja/'.$slug,
            'title' => $title,
            'subtitle' => null,
            'body' => 'catalogue row',
            'city' => null,
            'card_json' => json_encode(['test' => true]),
            'status' => 'ACTIVE',
            'indexed_at' => now(),
        ]);
    }
}
