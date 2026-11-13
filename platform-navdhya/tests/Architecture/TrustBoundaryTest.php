<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Modules\Support\Domain\CaseLifecycle;
use PHPUnit\Framework\TestCase;

/**
 * Enforces §3 / §4 ownership boundaries & Deptrac discipline across Trust modules.
 */
final class TrustBoundaryTest extends TestCase
{
    public function test_domain_trust_never_imports_application_modules(): void
    {
        $trustDir = dirname(__DIR__, 2).'/app/Domain/Trust';
        $this->assertDirectoryExists($trustDir);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($trustDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/\buse\s+\\\\?App\\\\Modules\\\\/',
                $source,
                sprintf('Domain/Trust file %s must never import App\\Modules.', $file->getPathname())
            );
        }
    }

    public function test_support_case_status_column_width_fits_all_states(): void
    {
        CaseLifecycle::assertColumnWidthSafe();
        $this->assertTrue(true);
    }

    public function test_trust_modules_never_directly_mutate_finance_or_assignments(): void
    {
        foreach (['Feedback', 'Support', 'Incident'] as $module) {
            $dir = dirname(__DIR__, 2)."/app/Modules/{$module}";
            if (! is_dir($dir)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $source = (string) file_get_contents($file->getPathname());
                $this->assertDoesNotMatchRegularExpression(
                    '/App\\\\Modules\\\\Finance\\\\Models\\\\|PanditAssignment::(create|query)\b/',
                    $source,
                    sprintf('Module %s file %s violates §4 ownership boundaries.', $module, $file->getPathname())
                );
            }
        }
    }
}
