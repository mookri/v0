<?php

namespace App\Domain\Verification\Contracts;

interface IndicTransliterator
{
    public function foldToLatin(string $text): string;

    public function toPhoneticKey(string $text): string;
}

