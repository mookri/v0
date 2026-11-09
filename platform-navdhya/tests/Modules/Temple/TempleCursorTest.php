<?php

namespace Tests\Modules\Temple;

use App\Models\User;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TempleManager;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Temple cursor forwarding (S12): every Temple list action read `limit`
 * but never forwarded the caller's `cursor` to CursorPagination — the
 * advertised next_cursor could not advance, so each "next page" silently
 * replayed page 1 (infinite first page). These walks pin the
 * Pandit-identical threading across the public, partner, dashboard and
 * admin surfaces: each page carries rows, never replays an earlier row,
 * and the walk terminates with every seeded row exactly once.
 */
final class TempleCursorTest extends TenantIntegrationTestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedTemple(Tenant $tenant, array $overrides = [], bool $asManager = true): Temple
    {
        $temple = Temple::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => Temple::STATUS_ACTIVE,
            'verification_status' => Temple::VERIFICATION_VERIFIED,
            ...$overrides,
        ]);

        if ($asManager && auth()->check()) {
            TempleManager::query()->firstOrCreate(
                [
                    'tenant_id' => (string) $tenant->getKey(),
                    'temple_id' => (string) $temple->getKey(),
                    'user_id' => (string) auth()->id(),
                ],
                [
                    'role' => TempleManager::ROLE_OWNER,
                    'invited_at' => now()->toISOString(),
                    'accepted_at' => now()->toISOString(),
                ]
            );
        }

        return $temple;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedAssignment(Tenant $tenant, Temple $temple, array $overrides = []): TemplePuja
    {
        return TemplePuja::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'temple_id' => (string) $temple->getKey(),
            'status' => TemplePuja::STATUS_ACTIVE,
            ...$overrides,
        ]);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: array<string, string>}
     */
    private function actingInTenant(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        return [$tenant, $user, $this->tenantHeaders($tenant)];
    }

    /**
     * Walk every cursor page of a list URL (which must already carry
     * ?limit=...) and return all ids in order. Fails if any page is
     * empty, replays a row from an earlier page, or if the walk never
     * terminates.
     *
     * @param  array<string, string>  $headers
     * @return list<string>
     */
    private function walkIds(string $url, array $headers): array
    {
        $ids = [];
        $cursor = null;
        $pages = 0;

        do {
            $pageUrl = $cursor === null ? $url : $url.'&cursor='.urlencode($cursor);
            $response = $this->getJson($pageUrl, $headers)->assertOk();
            $pageIds = array_column($response->json('data'), 'id');

            $this->assertNotEmpty($pageIds, 'a page of the walk must carry rows');
            $this->assertSame(
                [],
                array_values(array_intersect($ids, $pageIds)),
                'page '.$pages.' replayed a row from an earlier page',
            );

            $ids = array_merge($ids, $pageIds);
            $cursor = $response->json('meta.page.next_cursor');
            $pages++;

            $this->assertLessThan(10, $pages, 'the cursor walk never terminated');
        } while ($cursor !== null);

        return $ids;
    }

    public function test_public_profiles_cursor_walks_every_row_without_replay(): void
    {
        [$tenant, , $headers] = $this->actingInTenant();

        $one = $this->seedTemple($tenant);
        $two = $this->seedTemple($tenant);
        $three = $this->seedTemple($tenant);

        $ids = $this->walkIds('/api/v1/temple/profiles?limit=2', $headers);

        $this->assertCount(3, $ids);
        foreach ([$one, $two, $three] as $temple) {
            $this->assertContains((string) $temple->getKey(), $ids);
        }
    }

    public function test_public_pujas_cursor_walks_every_row_without_replay(): void
    {
        [$tenant, , $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);

        $one = $this->seedAssignment($tenant, $temple);
        $two = $this->seedAssignment($tenant, $temple);
        $three = $this->seedAssignment($tenant, $temple);

        $ids = $this->walkIds('/api/v1/temple/profiles/'.$temple->getKey().'/pujas?limit=2', $headers);

        $this->assertCount(3, $ids);
        foreach ([$one, $two, $three] as $assignment) {
            $this->assertContains((string) $assignment->getKey(), $ids);
        }
    }

    public function test_partner_temples_cursor_walks_every_row_without_replay(): void
    {
        [$tenant, $user, $headers] = $this->actingInTenant();

        $managed = [];
        foreach (range(1, 3) as $ignored) {
            $temple = $this->seedTemple($tenant, asManager: false);
            TempleManager::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'user_id' => $user->getAuthIdentifier(),
                'role' => TempleManager::ROLE_OWNER,
            ]);
            $managed[] = $temple;
        }

        $ids = $this->walkIds('/api/v1/temple/partner/temples?limit=2', $headers);

        $this->assertCount(3, $ids);
        foreach ($managed as $temple) {
            $this->assertContains((string) $temple->getKey(), $ids);
        }
    }

    public function test_partner_puja_list_cursor_walks_every_row_without_replay(): void
    {
        [$tenant, , $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);

        $this->seedAssignment($tenant, $temple);
        $this->seedAssignment($tenant, $temple);
        $this->seedAssignment($tenant, $temple);

        $ids = $this->walkIds(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/pujas?limit=2',
            $headers,
        );

        $this->assertCount(3, $ids);
    }

    public function test_dashboard_pujas_cursor_walks_every_row_without_replay(): void
    {
        [$tenant, , $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);

        $this->seedAssignment($tenant, $temple);
        $this->seedAssignment($tenant, $temple);
        $this->seedAssignment($tenant, $temple);

        $ids = $this->walkIds(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/dashboard/pujas?limit=2',
            $headers,
        );

        $this->assertCount(3, $ids);
    }

    public function test_admin_profiles_cursor_walks_every_row_without_replay(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['temple:profile:view']);
        $headers = $this->tenantHeaders($tenant);

        $this->seedTemple($tenant);
        $this->seedTemple($tenant);
        $this->seedTemple($tenant);

        $ids = $this->walkIds('/api/v1/temple/admin/profiles?limit=2', $headers);

        $this->assertCount(3, $ids);
    }
}
