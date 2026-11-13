<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Support\AksharaSegmenter;
use App\Modules\Search\Support\KeyFolder;
use App\Modules\Search\Support\TransliterationSchemes;
use Tests\TestCase;

class TransliterationParityTest extends TestCase
{
    public function test_akshara_segmenter_segments_clusters(): void
    {
        $aksharas = AksharaSegmenter::segment('रुद्राभिषेक');
        // रु, द्र, ा, भि, षे, क or conjunct grouped
        $this->assertNotEmpty($aksharas);
        $this->assertGreaterThanOrEqual(4, count($aksharas));

        $count = AksharaSegmenter::countGraphemes('पूजा');
        $this->assertSame(2, $count);
    }

    public function test_transliteration_vectors_match_expected_outputs(): void
    {
        $fixturePath = base_path('tests/Fixtures/Search/translit-vectors.json');
        $this->assertFileExists($fixturePath);

        $vectors = json_decode((string) file_get_contents($fixturePath), true);
        $this->assertIsArray($vectors);
        $this->assertNotEmpty($vectors);

        foreach ($vectors as $v) {
            $deva = $v['deva'];
            $expectedIast = $v['iast'];
            $expectedFold = $v['fold'];

            $actualIast = TransliterationSchemes::devaToIast($deva);
            $this->assertSame(mb_strtolower($expectedIast, 'UTF-8'), mb_strtolower($actualIast, 'UTF-8'), "IAST failed for {$deva}");

            $actualFold = TransliterationSchemes::foldToLatin($deva);
            $this->assertSame($expectedFold, $actualFold, "Fold failed for {$deva}");

            $keySet = KeyFolder::generateKeySet($deva);
            $this->assertSame($deva, $keySet['display']);
            $this->assertSame($deva, $keySet['key_dev']);
            $this->assertSame($actualIast, $keySet['key_lat']);
            $this->assertSame($expectedFold, $keySet['key_fold']);
            $this->assertNotEmpty($keySet['key_phon']);
            $this->assertContains($expectedFold, $keySet['key_tr']);
        }
    }

    public function test_iast_preserves_scholarly_sanskrit_without_destruction(): void
    {
        $iast = 'rudrābhiṣeka';
        $keySet = KeyFolder::generateKeySet($iast);

        $this->assertSame('rudrābhiṣeka', $keySet['display']);
        $this->assertSame('rudrābhiṣeka', $keySet['key_lat']);
        $this->assertSame('rudrabhisheka', $keySet['key_fold']);
        $this->assertContains('rudrabhishek', $keySet['key_tr']);
    }

    public function test_export_table_has_all_expected_schema_keys(): void
    {
        $table = TransliterationSchemes::exportTable();

        $this->assertSame(1, $table['schema_version']);
        $this->assertArrayHasKey('independent_vowels', $table);
        $this->assertArrayHasKey('vowel_matras', $table);
        $this->assertArrayHasKey('consonants', $table);
        $this->assertArrayHasKey('latin_fold', $table);
        $this->assertArrayHasKey('phonetic_equivalences', $table);
    }
}
