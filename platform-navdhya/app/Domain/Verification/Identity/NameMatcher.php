<?php

namespace App\Domain\Verification\Identity;

use App\Domain\Verification\Contracts\IndicTransliterator;

/**
 * Transliteration-aware Indian name matcher (§8.1, §9.2 / T-TR05-04).
 *
 * Strictly advisory: returns NameMatchBand for human reviewer triage and
 * MUST NEVER drive automated rejections (R-08).
 */
final class NameMatcher
{
    private const HONORIFICS = [
        'shri', 'sri', 'shrimati', 'smt', 'swami', 'mahant', 'acharya',
        'pandit', 'pt', 'm/s', 'ms', 'mr', 'mrs', 'dr', 'kumari',
    ];

    public function __construct(private readonly IndicTransliterator $transliterator)
    {
    }

    public function compare(string $leftName, string $rightName): NameMatchResult
    {
        $normLeft = $this->normalize($leftName);
        $normRight = $this->normalize($rightName);

        if ($normLeft !== '' && $normLeft === $normRight) {
            return new NameMatchResult(1.0, NameMatchBand::EXACT_NORMALIZED, $normLeft, $normRight);
        }

        $tokensLeft = $this->sortedTokens($normLeft);
        $tokensRight = $this->sortedTokens($normRight);

        if ($tokensLeft !== [] && $tokensLeft === $tokensRight) {
            return new NameMatchResult(0.98, NameMatchBand::EXACT_NORMALIZED, $normLeft, $normRight);
        }

        // Phonetic equality via Search TransliterationSchemes (e.g., Shastri / Śāstrī / शास्त्री).
        $phoneticLeft = $this->transliterator->toPhoneticKey(implode(' ', $tokensLeft));
        $phoneticRight = $this->transliterator->toPhoneticKey(implode(' ', $tokensRight));
        if ($phoneticLeft !== '' && $phoneticLeft === $phoneticRight) {
            return new NameMatchResult(0.95, NameMatchBand::EXACT_NORMALIZED, $normLeft, $normRight);
        }

        $jaccard = $this->jaccardWithInitialSupport($tokensLeft, $tokensRight);
        $stringSim = $this->normalizedLevenshtein($phoneticLeft, $phoneticRight);
        $score = round(max($jaccard * 0.65 + $stringSim * 0.35, ($jaccard + $stringSim) / 2.0), 4);

        $band = match (true) {
            $score >= 0.90 => NameMatchBand::EXACT_NORMALIZED,
            $score >= 0.65 => NameMatchBand::MINOR_VARIANT_REVIEW,
            default => NameMatchBand::SUBSTANTIVE_MISMATCH_ESCALATE,
        };

        return new NameMatchResult($score, $band, $normLeft, $normRight);
    }

    private function normalize(string $name): string
    {
        $folded = $this->transliterator->foldToLatin($name);
        $spaced = preg_replace('/[.,]+/', ' ', $folded) ?? $folded;
        $cleaned = preg_replace('/[^a-z0-9\s]/', ' ', $spaced) ?? $spaced;
        $parts = preg_split('/\s+/', trim($cleaned)) ?: [];

        $filtered = array_values(array_filter($parts, static function (string $tok): bool {
            return $tok !== '' && ! in_array($tok, self::HONORIFICS, true);
        }));

        return implode(' ', $filtered);
    }

    /**
     * @return list<string>
     */
    private function sortedTokens(string $normalized): array
    {
        $tokens = $normalized === '' ? [] : explode(' ', $normalized);
        sort($tokens);

        return $tokens;
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    private function jaccardWithInitialSupport(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }

        $matchedA = 0;
        foreach ($a as $ta) {
            foreach ($b as $tb) {
                if ($ta === $tb || (strlen($ta) === 1 && str_starts_with($tb, $ta)) || (strlen($tb) === 1 && str_starts_with($ta, $tb))) {
                    $matchedA++;
                    break;
                }
            }
        }

        $matchedB = 0;
        foreach ($b as $tb) {
            foreach ($a as $ta) {
                if ($tb === $ta || (strlen($tb) === 1 && str_starts_with($ta, $tb)) || (strlen($ta) === 1 && str_starts_with($tb, $ta))) {
                    $matchedB++;
                    break;
                }
            }
        }

        return ($matchedA + $matchedB) / (count($a) + count($b));
    }

    private function normalizedLevenshtein(string $a, string $b): float
    {
        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen === 0) {
            return 0.0;
        }

        return 1.0 - (levenshtein(substr($a, 0, 255), substr($b, 0, 255)) / $maxLen);
    }
}

