<?php

namespace App\Modules\Search\Services;

use App\Modules\Search\Support\TransliterationMap;
use App\Modules\Search\Models\SearchSynonym;

/**
 * S59 — Unicode-aware normalizer + tokenizer.
 *
 * Output tokens are always Latin (Devanagari is folded first). A second
 * stream of Devanagari akshara bigrams is appended for partial matching
 * against the MySQL ngram FULLTEXT index.
 */
class SearchNormalizer
{
    private const LATIN_STOPWORDS = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'but', 'by', 'for', 'from',
        'in', 'is', 'it', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was',
        'with', 'you', 'your', 'for', 'of', 'to',
    ];

    private const HINDI_STOPWORDS = [
        'का', 'के', 'की', 'है', 'और', 'में', 'एक', 'यह', 'तो', 'भी',
        'से', 'पर', 'ने', 'को', 'हो', 'था', 'थे', 'थी', 'हैं', 'हुआ',
        'वह', 'जो', 'कि', 'या', 'अपने', 'अपना', 'अपनी',
    ];

    /**
     * Fold + tokenize a free-text value into a deduplicated token list.
     *
     * @return string[]
     */
    public function tokenize(string $value, string $locale = 'en'): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $tokens = [];

        // 1. Devanagari akshara bigrams (raw) for ngram recall.
        if (preg_match('/\p{Devanagari}/u', $value)) {
            $chars = $this->devanagariChars($value);
            for ($i = 0; $i + 1 < count($chars); $i++) {
                $bigram = $chars[$i] . $chars[$i + 1];
                if (mb_strlen($bigram, 'UTF-8') >= 2) {
                    $tokens[] = mb_strtolower($bigram, 'UTF-8');
                }
            }
        }

        // 2. Fold Devanagari -> Latin, then split.
        $folded = TransliterationMap::fold($value);
        $folded = mb_strtolower($folded, 'UTF-8');
        $folded = preg_replace('/[^a-z0-9]+/u', ' ', $folded);
        $parts = preg_split('/\s+/u', trim($folded)) ?: [];

        $stop = array_merge(self::LATIN_STOPWORDS, self::HINDI_STOPWORDS);
        $stopSet = array_flip($stop);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || mb_strlen($part, 'UTF-8') < 2) {
                continue;
            }
            if (isset($stopSet[$part])) {
                continue;
            }
            $tokens[] = $part;
        }

        // 3. Synonym expansion (1 hop, capped at 3 expansions).
        $expanded = $this->expandSynonyms($tokens, $locale);
        foreach ($expanded as $t) {
            $tokens[] = $t;
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @param string[] $tokens
     * @return string[] synonym tokens only (not the originals)
     */
    public function expandSynonyms(array $tokens, string $locale = 'en'): array
    {
        $out = [];
        $added = 0;
        $cap = 3;

        foreach ($tokens as $token) {
            if ($added >= $cap) {
                break;
            }
            try {
                $syns = SearchSynonym::where('term', $token)
                    ->where('locale', $locale)
                    ->pluck('synonym')
                    ->all();
            } catch (\Throwable $e) {
                $syns = [];
            }
            foreach ($syns as $s) {
                if ($added >= $cap) {
                    break;
                }
                $s = mb_strtolower(trim((string) $s), 'UTF-8');
                if ($s !== '' && ! in_array($s, $tokens, true) && ! in_array($s, $out, true)) {
                    $out[] = $s;
                    $added++;
                }
            }
        }

        return $out;
    }

    /**
     * Normalize a full string for exact-match comparisons (no tokenization).
     */
    public function normalize(string $value): string
    {
        $folded = TransliterationMap::fold($value);
        $folded = mb_strtolower($folded, 'UTF-8');
        $folded = preg_replace('/\s+/u', ' ', trim($folded));
        return $folded;
    }

    /**
     * @return string[]
     */
    private function devanagariChars(string $value): array
    {
        preg_match_all('/\p{Devanagari}/u', $value, $m);
        /** @var list<string> $chars */
        $chars = $m[0];
        return $chars;
    }
}
