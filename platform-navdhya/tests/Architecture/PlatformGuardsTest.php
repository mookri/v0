<?php

namespace Tests\Architecture;

use Tests\TenantIntegrationTestCase;

/**
 * Platform guardrails (process gates, zero runtime risk):
 * offset pagination is banned (cursor only), the JSON body cap rides the
 * api group, and the hottest reads must not full-scan.
 */
final class PlatformGuardsTest extends TenantIntegrationTestCase
{
    /**
     * @return list<string>
     */
    private function phpFiles(string $dir): array
    {
        $files = [];
        if (! is_dir($dir)) {
            return $files;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    public function test_no_offset_pagination_in_app_code(): void
    {
        $violations = [];
        foreach ($this->phpFiles(base_path('app')) as $file) {
            $content = (string) file_get_contents($file);
            if (preg_match('/->\s*(offset|skip|simplePaginate|paginate)\s*\(/', $content)) {
                $violations[] = $file;
            }
        }

        $this->assertSame([], $violations, 'Offset pagination is banned; use CursorPagination.');
    }

    public function test_json_body_limit_rejects_oversized_bodies(): void
    {
        // Authenticated (limit.body runs after auth in pipeline priority —
        // unauthenticated oversized requests correctly get 401 first).
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $big = ['track' => 'pandit', 'pad' => str_repeat('x', 2 * 1024 * 1024)];

        $response = $this->actingAs($user)->postJson('/api/v1/onboarding/applications', $big, [
            'X-Tenant-Id' => (string) $tenant->getKey(),
        ]);

        $response->assertStatus(413);
        $response->assertJsonPath('error.code', 'payload_too_large');
    }
}
