<?php

namespace App\Modules\Search\Support;

use App\Domain\Verification\Contracts\IndicTransliterator;

/**
 * Reuses Search TransliterationSchemes for Verification Indic NameMatcher without crossing layer boundaries.
 */
final class SearchIndicTransliteratorAdapter implements IndicTransliterator
{
    public function foldToLatin(string $text): string
    {
        return TransliterationSchemes::foldToLatin($text);
    }

    public function toPhoneticKey(string $text): string
    {
        return TransliterationSchemes::toPhoneticKey($text);
    }
}

