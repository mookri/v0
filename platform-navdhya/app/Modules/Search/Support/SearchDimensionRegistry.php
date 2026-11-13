<?php

namespace App\Modules\Search\Support;

class SearchDimensionRegistry
{
    public const SURFACES = [
        'web_header',
        'web_search_page',
        'customer_home',
        'customer_catalog',
        'partner_portal',
    ];

    public const SCRIPT_FAMILIES = [
        'devanagari',
        'latin',
        'iast',
        'mixed',
    ];

    public const ZERO_RESULT_REASONS = [
        'no_token',
        'filtered_out',
        'budget',
        'region',
        'stale_index',
        'error',
    ];

    public const SELECTION_ACTIONS = [
        'click',
        'quick_view',
        'book_direct',
    ];

    public static function isValidSurface(string $surface): bool
    {
        return in_array($surface, self::SURFACES, true);
    }

    public static function isValidScript(string $script): bool
    {
        return in_array($script, self::SCRIPT_FAMILIES, true);
    }

    public static function isValidZeroResultReason(string $reason): bool
    {
        return in_array($reason, self::ZERO_RESULT_REASONS, true);
    }

    public static function isValidAction(string $action): bool
    {
        return in_array($action, self::SELECTION_ACTIONS, true);
    }

    /**
     * Detect script family from text: devanagari, latin, iast, or mixed.
     */
    public static function detectScript(string $text): string
    {
        $hasDevanagari = (bool) preg_match('/[\x{0900}-\x{097F}]/u', $text);
        $hasIast = (bool) preg_match('/[āīūṛṝḷḹṃḥṅñṭḍṇśṣḻ]/u', $text);
        $hasBasicLatin = (bool) preg_match('/[a-zA-Z]/u', $text);

        if ($hasDevanagari && ($hasBasicLatin || $hasIast)) {
            return 'mixed';
        }

        if ($hasDevanagari) {
            return 'devanagari';
        }

        if ($hasIast) {
            return 'iast';
        }

        return 'latin';
    }
}
