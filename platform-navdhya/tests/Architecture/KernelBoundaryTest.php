<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Doc 01 §7(e): the platform kernel has ZERO imports of App\Modules, and
 * Support never imports from Modules either ("Platform ↛ Modules"). This
 * source-level scan complements Deptrac by also catching string-based
 * class references that static layering tools cannot see.
 *
 * Docblock mentions of the modules path and the registry's computed
 * provider-class strings are not imports and are allowed.
 */
final class KernelBoundaryTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function kernelSourceFilesProvider(): array
    {
        $files = [];

        // Data providers run before the Laravel app boots, so resolve paths
        // relative to this file instead of using base_path().
        $basePath = dirname(__DIR__, 2);

        foreach (['app/Platform', 'app/Support'] as $directory) {
            foreach (self::phpFiles($basePath.'/'.$directory) as $file) {
                $files[$file] = [$file];
            }
        }

        return $files;
    }

    #[DataProvider('kernelSourceFilesProvider')]
    public function test_kernel_and_support_never_import_app_modules(string $file): void
    {
        $code = self::stripComments((string) file_get_contents($file));

        $this->assertDoesNotMatchRegularExpression(
            '/(^\s*|\s)use\s+\\\\?App\\\\Modules|new\s+\\\\?App\\\\Modules|App\\\\Modules\\\\[A-Za-z_]+::/',
            $code,
            sprintf('Kernel/support file %s must not import or statically reference App\Modules code (doc 01 §7).', $file),
        );
    }

    private static function stripComments(string $source): string
    {
        $withoutBlockComments = preg_replace('!/\*.*?\*/!s', '', $source) ?? $source;

        return preg_replace('!//.*$!m', '', $withoutBlockComments) ?? $withoutBlockComments;
    }

    /**
     * @return list<string>
     */
    private static function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $files[] = $item->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
