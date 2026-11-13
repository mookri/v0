<?php

namespace Tests\Architecture;

use App\Domain\Jyotish\Support\GotraList;
use App\Domain\Jyotish\Support\JyotishVocabularies;
use PHPUnit\Framework\TestCase;

/**
 * Cross-language parity test for canonical Jyotish vocabularies (T-JY00-14).
 * Enforces AC-T-JY00-14: TypeScript and PHP canonical lists match identically.
 */
final class ContractsParityTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootDir = dirname(__DIR__, 3);
    }

    public function test_ayanamsa_parity(): void
    {
        $tsAyanamsa = $this->extractTsConstant('packages/validation/src/constants/jyotish.ts', 'CANONICAL_AYANAMSA');
        $this->assertSame(
            JyotishVocabularies::CANONICAL_AYANAMSA,
            $tsAyanamsa,
            'CANONICAL_AYANAMSA in TypeScript and PHP must match identically.',
        );
    }

    public function test_house_systems_parity(): void
    {
        $tsHouseSystems = $this->extractTsConstant('packages/validation/src/constants/jyotish.ts', 'CANONICAL_HOUSE_SYSTEMS');
        $this->assertSame(
            JyotishVocabularies::CANONICAL_HOUSE_SYSTEMS,
            $tsHouseSystems,
            'CANONICAL_HOUSE_SYSTEMS in TypeScript and PHP must match identically.',
        );
    }

    public function test_vargas_parity(): void
    {
        $tsVargas = $this->extractTsConstant('packages/validation/src/constants/jyotish.ts', 'CANONICAL_VARGAS');
        $this->assertSame(
            JyotishVocabularies::CANONICAL_VARGAS,
            $tsVargas,
            'CANONICAL_VARGAS in TypeScript and PHP must match identically.',
        );
    }

    public function test_dasha_systems_parity(): void
    {
        $tsDashaSystems = $this->extractTsConstant('packages/validation/src/constants/jyotish.ts', 'CANONICAL_DASHA_SYSTEMS');
        $this->assertSame(
            JyotishVocabularies::CANONICAL_DASHA_SYSTEMS,
            $tsDashaSystems,
            'CANONICAL_DASHA_SYSTEMS in TypeScript and PHP must match identically.',
        );
    }

    public function test_gotra_parity(): void
    {
        $tsGotras = $this->extractTsConstant('packages/validation/src/index.ts', 'CANONICAL_GOTRAS');
        $this->assertSame(
            GotraList::CANONICAL,
            $tsGotras,
            'CANONICAL_GOTRAS in TypeScript and PHP GotraList must match identically.',
        );
    }

    /**
     * @return list<string>
     */
    private function extractTsConstant(string $relativeFile, string $constantName): array
    {
        $fullPath = $this->rootDir . '/' . $relativeFile;
        $this->assertFileExists($fullPath);

        $content = (string) file_get_contents($fullPath);
        $pattern = '/export\s+const\s+' . preg_quote($constantName, '/') . '\s*=\s*\[(.*?)\]\s*as\s+const/s';

        $this->assertSame(1, preg_match($pattern, $content, $matches), "Could not find constant {$constantName} in {$relativeFile}");

        preg_match_all("/['\"]([^'\"]+)['\"]/", $matches[1], $itemMatches);

        return $itemMatches[1];
    }
}
