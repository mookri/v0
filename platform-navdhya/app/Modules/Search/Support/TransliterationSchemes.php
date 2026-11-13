<?php

namespace App\Modules\Search\Support;

class TransliterationSchemes
{
    private const INDEPENDENT_VOWELS = [
        'अ' => 'a', 'आ' => 'ā', 'इ' => 'i', 'ई' => 'ī', 'उ' => 'u', 'ऊ' => 'ū',
        'ऋ' => 'ṛ', 'ॠ' => 'ṝ', 'ऌ' => 'ḷ', 'ॡ' => 'ḹ',
        'ए' => 'e', 'ऐ' => 'ai', 'ओ' => 'o', 'औ' => 'au',
    ];

    private const VOWEL_MATRAS = [
        'ा' => 'ā', 'ि' => 'i', 'ी' => 'ī', 'ु' => 'u', 'ू' => 'ū',
        'ृ' => 'ṛ', 'ॄ' => 'ṝ', 'ॢ' => 'ḷ', 'ॣ' => 'ḹ',
        'े' => 'e', 'ै' => 'ai', 'ो' => 'o', 'ौ' => 'au',
    ];

    private const CONSONANTS = [
        'क' => 'k', 'ख' => 'kh', 'ग' => 'g', 'घ' => 'gh', 'ङ' => 'ṅ',
        'च' => 'c', 'छ' => 'ch', 'ज' => 'j', 'झ' => 'jh', 'ञ' => 'ñ',
        'ट' => 'ṭ', 'ठ' => 'ṭh', 'ड' => 'ḍ', 'ढ' => 'ḍh', 'ण' => 'ṇ',
        'त' => 't', 'थ' => 'th', 'द' => 'd', 'ध' => 'dh', 'न' => 'n',
        'प' => 'p', 'फ' => 'ph', 'ब' => 'b', 'भ' => 'bh', 'म' => 'm',
        'य' => 'y', 'र' => 'r', 'ल' => 'l', 'व' => 'v',
        'श' => 'ś', 'ष' => 'ṣ', 'स' => 's', 'ह' => 'h',
    ];

    private const SPECIAL_SIGNS = [
        'ं' => 'ṃ',
        'ः' => 'ḥ',
        'ँ' => 'm̐',
        'ऽ' => "'",
    ];

    private const DIGITS = [
        '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
        '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9',
    ];

    /**
     * Convert Devanagari text to exact IAST (preserving Sanskrit scholarly precision).
     */
    public static function devaToIast(string $text): string
    {
        $aksharas = AksharaSegmenter::segment($text);
        $result = '';

        foreach ($aksharas as $akshara) {
            $result .= self::segmentToIast($akshara);
        }

        return $result;
    }

    private static function segmentToIast(string $akshara): string
    {
        // 1. Digits
        if (isset(self::DIGITS[$akshara])) {
            return self::DIGITS[$akshara];
        }

        // 2. Pure Independent vowel
        $firstChar = mb_substr($akshara, 0, 1, 'UTF-8');
        if (isset(self::INDEPENDENT_VOWELS[$firstChar])) {
            $base = self::INDEPENDENT_VOWELS[$firstChar];
            $modifier = mb_substr($akshara, 1, null, 'UTF-8');
            $modIast = '';
            for ($i = 0; $i < mb_strlen($modifier, 'UTF-8'); $i++) {
                $m = mb_substr($modifier, $i, 1, 'UTF-8');
                $modIast .= self::SPECIAL_SIGNS[$m] ?? '';
            }
            return $base . $modIast;
        }

        // 3. Consonants cluster + optional matra + optional modifier
        $chars = preg_split('//u', $akshara, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = '';
        $lastWasVirama = false;
        $hasMatra = false;
        $inConsonantCluster = false;

        $n = count($chars);
        for ($i = 0; $i < $n; $i++) {
            $c = $chars[$i];
            if (isset(self::CONSONANTS[$c])) {
                $inConsonantCluster = true;
                $out .= self::CONSONANTS[$c];
                $next = $chars[$i + 1] ?? null;
                if ($next === '्') {
                    // virama suppresses inherent vowel
                    $lastWasVirama = true;
                    $i++; // skip virama
                } elseif ($next !== null && isset(self::VOWEL_MATRAS[$next])) {
                    $out .= self::VOWEL_MATRAS[$next];
                    $hasMatra = true;
                    $lastWasVirama = false;
                    $i++; // skip matra
                } else {
                    // Inherent 'a'
                    $out .= 'a';
                    $lastWasVirama = false;
                }
            } elseif (isset(self::VOWEL_MATRAS[$c])) {
                $out .= self::VOWEL_MATRAS[$c];
                $hasMatra = true;
                $lastWasVirama = false;
            } elseif (isset(self::SPECIAL_SIGNS[$c])) {
                $out .= self::SPECIAL_SIGNS[$c];
            } else {
                $out .= $c;
            }
        }

        return $out;
    }

    /**
     * Fold IAST / Devanagari text to plain ASCII lowercase (no diacritics).
     */
    public static function foldToLatin(string $text): string
    {
        $iast = preg_match('/[\x{0900}-\x{097F}]/u', $text) ? self::devaToIast($text) : $text;
        $lower = mb_strtolower($iast, 'UTF-8');

        $map = [
            'ā' => 'a', 'ī' => 'i', 'ū' => 'u', 'ṛ' => 'ri', 'ṝ' => 'ri', 'ḷ' => 'li', 'ḹ' => 'li',
            'ś' => 'sh', 'ṣ' => 'sh', 'ṭ' => 't', 'ṭh' => 'th', 'ḍ' => 'd', 'ḍh' => 'dh', 'ṇ' => 'n',
            'ṅ' => 'n', 'ñ' => 'n', 'ṃ' => 'm', 'ḥ' => 'h', "m̐" => 'm',
        ];

        return strtr($lower, $map);
    }

    /**
     * Generate phonetic representation for search fuzzy matching.
     */
    public static function toPhoneticKey(string $text): string
    {
        $fold = self::foldToLatin($text);
        $clean = preg_replace('/[^a-z0-9]+/u', '', $fold) ?? '';

        $phoneticMap = [
            'sh' => 's',
            'ee' => 'i',
            'oo' => 'u',
            'aa' => 'a',
            'kh' => 'k',
            'gh' => 'g',
            'chh' => 'c',
            'ch' => 'c',
            'jh' => 'j',
            'th' => 't',
            'dh' => 'd',
            'ph' => 'p',
            'bh' => 'b',
            'w' => 'v',
            'm' => 'n',
        ];

        $key = strtr($clean, $phoneticMap);
        // Collapse adjacent duplicate letters (e.g. ss -> s, tt -> t)
        return preg_replace('/(.)\1+/u', '$1', $key) ?? $key;
    }

    /**
     * Export full mapping definitions for TypeScript generator and client consumption.
     *
     * @return array<string, mixed>
     */
    public static function exportTable(): array
    {
        return [
            'schema_version' => 1,
            'independent_vowels' => self::INDEPENDENT_VOWELS,
            'vowel_matras' => self::VOWEL_MATRAS,
            'consonants' => self::CONSONANTS,
            'special_signs' => self::SPECIAL_SIGNS,
            'digits' => self::DIGITS,
            'latin_fold' => [
                'ā' => 'a', 'ī' => 'i', 'ū' => 'u', 'ṛ' => 'ri', 'ṝ' => 'ri', 'ḷ' => 'li', 'ḹ' => 'li',
                'ś' => 'sh', 'ṣ' => 'sh', 'ṭ' => 't', 'ṭh' => 'th', 'ḍ' => 'd', 'ḍh' => 'dh', 'ṇ' => 'n',
                'ṅ' => 'n', 'ñ' => 'n', 'ṃ' => 'm', 'ḥ' => 'h',
            ],
            'phonetic_equivalences' => [
                'sibilants' => ['ś', 'ṣ', 's', 'sh'],
                'vowels' => ['ṛ', 'ri', 'ru', 'r'],
                'nasals' => ['ṅ', 'ñ', 'ṇ', 'n', 'm', 'ṃ'],
                'aspirates' => [
                    ['k', 'kh'],
                    ['g', 'gh'],
                    ['c', 'ch', 'chh'],
                    ['j', 'jh'],
                    ['t', 'th'],
                    ['d', 'dh'],
                    ['p', 'ph'],
                    ['b', 'bh'],
                ],
                'labial' => ['v', 'w', 'b'],
            ],
        ];
    }
}
