<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Enforces Non-Negotiable Rule 1:
 * "Compute each astronomical quantity once per calculation and reuse it via a shared snapshot."
 *
 * Scans app/Domain/Jyotish/** and asserts that no class outside Astronomy/ and Ephemeris/
 * directly invokes raw solar/lunar longitude calculators.
 */
final class JyotishSnapshotTest extends TestCase
{
    public function test_no_downstream_domain_class_independently_calculates_ephemeris(): void
    {
        $basePath = dirname(__DIR__, 2);
        $directory = $basePath . '/app/Domain/Jyotish';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        $violations = [];

        foreach ($iterator as $item) {
            if (!$item->isFile() || $item->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $item->getPathname());

            // Allowed directories for raw astronomical calculation
            if (
                str_contains($path, '/app/Domain/Jyotish/Astronomy/') ||
                str_contains($path, '/app/Domain/Jyotish/Ephemeris/')
            ) {
                continue;
            }

            $source = (string) file_get_contents($item->getPathname());

            if (preg_match('/(SolarLongitude|LunarLongitude)::calculate\s*\(/', $source)) {
                $violations[] = "{$path} invokes Solar/LunarLongitude::calculate() directly instead of reading EphemerisSnapshot.";
            }

            if (preg_match('/MeeusSeriesProvider::calculate\s*\(/', $source)) {
                $violations[] = "{$path} invokes MeeusSeriesProvider directly instead of consuming EphemerisSnapshot.";
            }
        }

        $this->assertEmpty($violations, implode("\n", $violations));
    }
}

