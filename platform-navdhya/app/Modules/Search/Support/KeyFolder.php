<?php

namespace App\Modules\Search\Support;

class KeyFolder
{
    /**
     * Generate 6-key canonical representation set for an entity or query text.
     *
     * @return array{
     *   display: string,
     *   key_dev: string,
     *   key_lat: string,
     *   key_fold: string,
     *   key_phon: string,
     *   key_tr: string[]
     * }
     */
    public static function generateKeySet(string $text): array
    {
        $display = trim($text);
        if ($display === '') {
            return [
                'display' => '',
                'key_dev' => '',
                'key_lat' => '',
                'key_fold' => '',
                'key_phon' => '',
                'key_tr' => [],
            ];
        }

        $isDevanagari = (bool) preg_match('/[\x{0900}-\x{097F}]/u', $display);

        // 1. Devanagari representation
        $keyDev = $isDevanagari ? $display : '';

        // 2. IAST representation
        $keyLat = $isDevanagari ? TransliterationSchemes::devaToIast($display) : $display;

        // 3. Folded Latin (diacritics stripped, lowercase)
        $keyFold = TransliterationSchemes::foldToLatin($display);

        // 4. Phonetic key
        $keyPhon = TransliterationSchemes::toPhoneticKey($display);

        // 5. Variants (transliteration alternate spellings)
        $variants = [];
        $variants[] = $keyFold;

        // Variant without trailing 'a' (Hindi schwa deletion: rudrabhisheka -> rudrabhishek)
        if (str_ends_with($keyFold, 'a') && mb_strlen($keyFold, 'UTF-8') > 3) {
            $variants[] = mb_substr($keyFold, 0, -1, 'UTF-8');
        }

        // Common spelling variant 'ee' <-> 'i', 'oo' <-> 'u'
        if (str_contains($keyFold, 'i')) {
            $variants[] = str_replace('i', 'ee', $keyFold);
        }
        if (str_contains($keyFold, 'u')) {
            $variants[] = str_replace('u', 'oo', $keyFold);
        }

        // 'v' <-> 'w'
        if (str_contains($keyFold, 'v')) {
            $variants[] = str_replace('v', 'w', $keyFold);
        }

        $variants = array_values(array_unique(array_filter($variants)));

        return [
            'display' => $display,
            'key_dev' => $keyDev,
            'key_lat' => $keyLat,
            'key_fold' => $keyFold,
            'key_phon' => $keyPhon,
            'key_tr' => $variants,
        ];
    }
}
