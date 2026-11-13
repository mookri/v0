<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Architectural boundary enforcement for App\Domain\Jyotish (doc 01 §7, ADR-JY-01).
 *
 * Rules:
 *  - Domain/Jyotish is pure mathematical & astronomical domain logic.
 *  - Domain/Jyotish MUST NOT import App\Modules (zero module coupling).
 *  - Domain/Jyotish MUST NOT import Illuminate\Http, Illuminate\Routing, or Eloquent models.
 *  - Domain/Jyotish may depend only on App\Support and pure PHP.
 */
final class JyotishBoundaryTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function jyotishSourceFilesProvider(): array
    {
        $files = [];
        $basePath = dirname(__DIR__, 2);
        $directory = $basePath . '/app/Domain/Jyotish';

        foreach (self::phpFiles($directory) as $file) {
            $files[$file] = [$file];
        }

        return $files;
    }

    #[DataProvider('jyotishSourceFilesProvider')]
    public function test_jyotish_domain_never_imports_modules(string $file): void
    {
        $code = self::stripComments((string) file_get_contents($file));

        $this->assertDoesNotMatchRegularExpression(
            '/(^\s*|\s)use\s+\\\\?App\\\\Modules|new\s+\\\\?App\\\\Modules|App\\\\Modules\\\\[A-Za-z_]+::/',
            $code,
            sprintf('Domain file %s must not import or statically reference App\Modules code.', $file),
        );
    }

    #[DataProvider('jyotishSourceFilesProvider')]
    public function test_jyotish_domain_never_imports_http_or_eloquent(string $file): void
    {
        $code = self::stripComments((string) file_get_contents($file));

        $this->assertDoesNotMatchRegularExpression(
            '/(^\s*|\s)use\s+\\\\?Illuminate\\\\Http|Illuminate\\\\Routing|Illuminate\\\\Database\\\\Eloquent/',
            $code,
            sprintf('Domain file %s must not import HTTP or Eloquent layers.', $file),
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

