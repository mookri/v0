<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Enforces T-AP03-01 / T-AP03-02 tenancy scope-exit budget:
 *  - ZERO `withoutGlobalScopes` calls anywhere in HTTP controllers.
 *  - ZERO regression in already-migrated services (`PlatformScope::run()`).
 *  - Monotonic ceiling (`<= 54`) on legacy `withoutGlobalScopes()` sites
 *    documented in `docs/06-tenancy/scope-exit-inventory.md`.
 *  - Every `PlatformScope::run(...)` invocation specifies a non-empty reason.
 */
final class ScopeExitBudgetTest extends TestCase
{
    private const LEGACY_CEILING = 54;

    public function test_http_controllers_have_zero_without_global_scopes_calls(): void
    {
        $basePath = dirname(__DIR__, 2);
        $violations = [];

        foreach ($this->phpFilesUnder($basePath.'/app') as $relativePath => $fullPath) {
            if (! str_contains($relativePath, '/Http/Controllers/')) {
                continue;
            }

            $code = $this->stripComments((string) file_get_contents($fullPath));
            if (preg_match('/(->|::)withoutGlobalScopes\s*\(/', $code) === 1) {
                $violations[] = $relativePath;
            }
        }

        $this->assertSame(
            [],
            $violations,
            'HTTP controllers must NEVER call withoutGlobalScopes(); use ambient TenantContext or PlatformScope::run(): '.implode(', ', $violations),
        );
    }

    public function test_migrated_services_remain_free_of_without_global_scopes(): void
    {
        $basePath = dirname(__DIR__, 2);
        $migrated = [
            'app/Modules/Vendor/Application/StockReservationService.php',
            'app/Modules/Vendor/VendorOrderService.php',
            'app/Modules/Catalog/Domain/Pricing/TieredPriceResolver.php',
            'app/Modules/Catalog/Models/CatalogCategory.php',
            'app/Modules/Onboarding/Services/AppealService.php',
            'app/Domain/Privacy/Consent/ConsentManager.php',
        ];

        foreach ($migrated as $relativePath) {
            $fullPath = $basePath.'/'.$relativePath;
            $this->assertFileExists($fullPath);
            $code = $this->stripComments((string) file_get_contents($fullPath));
            $this->assertDoesNotMatchRegularExpression(
                '/(->|::)withoutGlobalScopes\s*\(/',
                $code,
                "{$relativePath} was migrated to PlatformScope::run() and must not reintroduce withoutGlobalScopes().",
            );
        }
    }

    public function test_total_without_global_scopes_stays_at_or_below_monotonic_budget(): void
    {
        $basePath = dirname(__DIR__, 2);
        $total = 0;
        $byFile = [];

        foreach ($this->phpFilesUnder($basePath.'/app') as $relativePath => $fullPath) {
            $code = $this->stripComments((string) file_get_contents($fullPath));
            $count = preg_match_all('/(->|::)withoutGlobalScopes\s*\(/', $code);
            if ($count > 0) {
                $total += $count;
                $byFile[$relativePath] = $count;
            }
        }

        $this->assertLessThanOrEqual(
            self::LEGACY_CEILING,
            $total,
            sprintf(
                'withoutGlobalScopes() count (%d) exceeded monotonic budget (%d). Offenders: %s',
                $total,
                self::LEGACY_CEILING,
                json_encode($byFile, JSON_THROW_ON_ERROR),
            ),
        );

        // Verify inventory doc lists every remaining file with an active withoutGlobalScopes() call.
        $inventoryPath = $basePath.'/docs/06-tenancy/scope-exit-inventory.md';
        $this->assertFileExists($inventoryPath);
        $inventoryText = (string) file_get_contents($inventoryPath);

        foreach (array_keys($byFile) as $file) {
            $this->assertStringContainsString(
                $file,
                $inventoryText,
                "Undocumented withoutGlobalScopes() usage in {$file}; either migrate to PlatformScope::run() or document in docs/06-tenancy/scope-exit-inventory.md.",
            );
        }
    }

    public function test_every_platform_scope_run_call_provides_non_empty_reason(): void
    {
        $basePath = dirname(__DIR__, 2);
        $violations = [];

        foreach ($this->phpFilesUnder($basePath.'/app') as $relativePath => $fullPath) {
            if ($relativePath === 'app/Platform/Tenancy/PlatformScope.php') {
                continue;
            }

            $code = $this->stripComments((string) file_get_contents($fullPath));
            if (preg_match_all('/PlatformScope::run\s*\(\s*([\'"][^\'"]*[\'"])?/', $code, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $arg = $match[1] ?? '';
                    if ($arg === "''" || $arg === '""') {
                        $violations[] = $relativePath;
                    }
                }
            }
        }

        $this->assertSame([], $violations, 'PlatformScope::run() requires a non-empty reason.');
    }

    /**
     * @return array<string, string>
     */
    private function phpFilesUnder(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $fullPath = str_replace('\\', '/', $file->getPathname());
            $basePath = str_replace('\\', '/', dirname(__DIR__, 2)).'/';
            $relative = str_starts_with($fullPath, $basePath)
                ? substr($fullPath, strlen($basePath))
                : $fullPath;

            $files[$relative] = $fullPath;
        }

        ksort($files);

        return $files;
    }

    private function stripComments(string $source): string
    {
        $tokens = token_get_all($source);
        $out = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }
                $out .= $token[1];
            } else {
                $out .= $token;
            }
        }

        return $out;
    }
}

