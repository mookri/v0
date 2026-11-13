<?php

namespace App\Modules\SeoContent\Support;

/**
 * 64-bit SimHash Generator and Near-Duplicate Detector.
 * Closes doorway page replication attacks by gating sibling similarity.
 */
final class SimHashGenerator
{
    /**
     * Compute 64-bit binary string SimHash of a text body.
     */
    public static function fingerprint(string $text): string
    {
        $normalized = strtolower(trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text)));
        $tokens = array_filter(explode(' ', $normalized), fn ($t) => strlen($t) >= 2);

        if (empty($tokens)) {
            return str_repeat('0', 64);
        }

        $v = array_fill(0, 64, 0);

        foreach ($tokens as $token) {
            $hashHex = substr(hash('sha256', $token), 0, 16);
            $high = hexdec(substr($hashHex, 0, 8));
            $low = hexdec(substr($hashHex, 8, 8));

            for ($i = 0; $i < 32; $i++) {
                $bit = ($low >> $i) & 1;
                $v[$i] += ($bit === 1) ? 1 : -1;
            }

            for ($i = 0; $i < 32; $i++) {
                $bit = ($high >> $i) & 1;
                $v[32 + $i] += ($bit === 1) ? 1 : -1;
            }
        }

        $fingerprint = '';
        for ($i = 0; $i < 64; $i++) {
            $fingerprint .= ($v[$i] > 0) ? '1' : '0';
        }

        return $fingerprint;
    }

    /**
     * Compute Hamming distance between two 64-bit binary string fingerprints.
     */
    public static function hammingDistance(string $hash1, string $hash2): int
    {
        $len = min(strlen($hash1), strlen($hash2), 64);
        $count = 0;

        for ($i = 0; $i < $len; $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Gate check: Returns true if two pages are sufficiently unique (Hamming distance >= threshold).
     */
    public static function isSufficientlyUnique(string $textA, string $textB, int $minHamming = 3): bool
    {
        $h1 = self::fingerprint($textA);
        $h2 = self::fingerprint($textB);

        return self::hammingDistance($h1, $h2) >= $minHamming;
    }
}
