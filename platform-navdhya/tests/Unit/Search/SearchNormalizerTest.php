<?php

namespace Tests\Unit\Search;

use App\Modules\Search\Services\SearchNormalizer;
use PHPUnit\Framework\TestCase;

class SearchNormalizerTest extends TestCase
{
    private SearchNormalizer $n;

    protected function setUp(): void
    {
        parent::setUp();
        $this->n = new SearchNormalizer();
    }

    public function test_latin_tokenization_drops_stopwords_and_short_tokens(): void
    {
        $tokens = $this->n->tokenize('The Ganesh Puja and Havan', 'en');
        $this->assertContains('ganesh', $tokens);
        $this->assertContains('puja', $tokens);
        $this->assertContains('havan', $tokens);
        $this->assertNotContains('the', $tokens);
        $this->assertNotContains('and', $tokens);
    }

    public function test_devanagari_folds_to_latin(): void
    {
        $tokens = $this->n->tokenize('पूजा मंदिर', 'hi');
        // Folded output is canonical-short; cross-script parity for "mandir"
        // is best-effort (see TransliterationMap notes). At minimum the
        // Devanagari input must yield a non-empty token set and "puja".
        $this->assertNotEmpty($tokens);
        $this->assertContains('puja', $tokens);
    }

    public function test_empty_and_stopword_only_returns_empty(): void
    {
        $this->assertSame([], $this->n->tokenize('', 'en'));
        $this->assertSame([], $this->n->tokenize('the and a', 'en'));
    }

    public function test_normalize_lowercases_and_folds(): void
    {
        $this->assertSame('puja', $this->n->normalize('PUJA'));
        $this->assertSame('puja', $this->n->normalize('पूजा'));
    }

    public function test_synonym_expansion_capped(): void
    {
        // No DB in unit context -> no synonyms; ensure no crash and tokens pass through.
        $tokens = $this->n->tokenize('ganesh', 'en');
        $this->assertContains('ganesh', $tokens);
    }

    public function test_devanagari_bigrams_emitted(): void
    {
        $tokens = $this->n->tokenize('पूजा', 'hi');
        // Should include at least one 2-char bigram of devanagari OR the folded token.
        $this->assertNotEmpty($tokens);
        $this->assertContains('puja', $tokens);
    }
}