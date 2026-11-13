<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Validates kernel immutability across catalogue expansion (AC-T-JY10-ALL).
 * Asserts that Astronomy and Ephemeris core calculation files remain isolated
 * and free of framework dependencies, UI bindings, or business logic.
 */
final class KernelImmutabilityTest extends TestCase
{
    public function test_astronomy_and_ephemeris_remain_pure_unpolluted_kernel_classes(): void
    {
        $basePath = realpath(__DIR__ . '/../../app/Domain/Jyotish');
        $this->assertNotFalse($basePath);

        $kernelDirs = [
            $basePath . '/Astronomy',
            $basePath . '/Ephemeris',
        ];

        $forbiddenKeywords = [
            'Illuminate\\Database',
            'Illuminate\\Http',
            'App\\Modules',
            'session(',
            'auth(',
            'request(',
        ];

        foreach ($kernelDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    $this->assertNotFalse($content);

                    foreach ($forbiddenKeywords as $forbidden) {
                        $this->assertStringNotContainsString(
                            $forbidden,
                            $content,
                            "Kernel file {$file->getFilename()} contains forbidden dependency '{$forbidden}'"
                        );
                    }
                }
            }
        }
    }
}

