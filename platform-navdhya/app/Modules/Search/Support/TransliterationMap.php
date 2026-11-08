<?php

namespace App\Modules\Search\Support;

/**
 * S59 — Devanagari -> Latin fold map (data, not code).
 *
 * Used by SearchNormalizer to fold Devanagari text into Latin tokens so
 * a Hindi query ("पूजा") and a Latin query ("puja") land on the same
 * token. The map is intentionally lossy (phonetic approximation) — it
 * exists for matching, not for display. Extend by appending entries;
 * never edit existing mappings without a parity-test update.
 */
final class TransliterationMap
{
    /**
     * @return array<string,string>
     */
    public static function map(): array
    {
        return [
            // Independent vowels (folded to short canonical forms for matching)
            'अ' => 'a', 'आ' => 'a', 'इ' => 'i', 'ई' => 'i', 'उ' => 'u',
            'ऊ' => 'u', 'ऋ' => 'ri', 'ए' => 'e', 'ऐ' => 'ai', 'ओ' => 'o',
            'औ' => 'au', 'अं' => 'am', 'अः' => 'ah',
            // Consonants
            'क' => 'k', 'ख' => 'kh', 'ग' => 'g', 'घ' => 'gh', 'ङ' => 'ng',
            'च' => 'ch', 'छ' => 'chh', 'ज' => 'j', 'झ' => 'jh', 'ञ' => 'ny',
            'ट' => 't', 'ठ' => 'th', 'ड' => 'd', 'ढ' => 'dh', 'ण' => 'n',
            'त' => 't', 'थ' => 'th', 'द' => 'd', 'ध' => 'dh', 'न' => 'n',
            'प' => 'p', 'फ' => 'ph', 'ब' => 'b', 'भ' => 'bh', 'म' => 'm',
            'य' => 'y', 'र' => 'r', 'ल' => 'l', 'व' => 'v', 'श' => 'sh',
            'ष' => 'sh', 'स' => 's', 'ह' => 'h', 'क्ष' => 'ksh',
            'त्र' => 'tr', 'ज्ञ' => 'gy', 'श्र' => 'shr',
            // Vowel signs (matras) — short canonical forms for matching
            'ा' => 'a', 'ि' => 'i', 'ी' => 'i', 'ु' => 'u', 'ू' => 'u',
            'ृ' => 'ri', 'े' => 'e', 'ै' => 'ai', 'ो' => 'o', 'ौ' => 'au',
            'ं' => 'm', 'ः' => 'h', '्' => '', 'ँ' => 'n',
            // Digits
            '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
            '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9',
        ];
    }

    public static function fold(string $value): string
    {
        $map = self::map();
        // Try longest keys first (conjuncts like क्ष are 2 code points).
        $keys = array_keys($map);
        usort($keys, fn ($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

        $out = '';
        $i = 0;
        $len = mb_strlen($value, 'UTF-8');
        while ($i < $len) {
            $matched = false;
            foreach ($keys as $k) {
                $kl = mb_strlen($k, 'UTF-8');
                if ($kl > 1 && mb_substr($value, $i, $kl, 'UTF-8') === $k) {
                    $out .= $map[$k];
                    $i += $kl;
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                continue;
            }
            $ch = mb_substr($value, $i, 1, 'UTF-8');
            if (isset($map[$ch])) {
                $out .= $map[$ch];
            } else {
                $out .= $ch;
            }
            $i++;
        }
        return $out;
    }
}
