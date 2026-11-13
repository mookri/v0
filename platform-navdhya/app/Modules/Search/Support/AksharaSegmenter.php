<?php

namespace App\Modules\Search\Support;

class AksharaSegmenter
{
    private const DEVA_VIRAMA = '\x{094D}';
    private const DEVA_NUKTA = '\x{093C}';
    private const DEVA_VOWELS_IND = '\x{0904}-\x{0914}\x{0960}-\x{0961}\x{0972}-\x{0977}';
    private const DEVA_CONSONANTS = '\x{0915}-\x{0939}\x{0958}-\x{095F}';
    private const DEVA_MATRAS = '\x{093A}-\x{094C}\x{094E}-\x{094F}\x{0955}-\x{0957}\x{0962}-\x{0963}';
    private const DEVA_MODIFIERS = '\x{0901}-\x{0903}\x{0951}-\x{0954}';

    /**
     * Segment a text string into orthographic aksharas (grapheme clusters).
     *
     * @return string[]
     */
    public static function segment(string $text): array
    {
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }

        // Akshara regex:
        // 1. Consonant cluster: (C (Nukta)? Virama)* C (Nukta)? (Matra)? (Modifiers)*
        // 2. Independent vowel: V (Modifiers)*
        // 3. Any other character / grapheme: \X
        $pattern = '/(?:(?:[' . self::DEVA_CONSONANTS . '][' . self::DEVA_NUKTA . ']?' . self::DEVA_VIRAMA . ')*[' . self::DEVA_CONSONANTS . '][' . self::DEVA_NUKTA . ']?[' . self::DEVA_MATRAS . ']?[' . self::DEVA_MODIFIERS . ']*|[' . self::DEVA_VOWELS_IND . '][' . self::DEVA_MODIFIERS . ']*|\X)/u';

        preg_match_all($pattern, $text, $matches);

        return $matches[0];
    }

    /**
     * Count grapheme / akshara length accurately (replaces byte strlen).
     */
    public static function countGraphemes(string $text): int
    {
        return count(self::segment(trim($text)));
    }
}
