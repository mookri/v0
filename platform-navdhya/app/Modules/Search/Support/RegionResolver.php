<?php

namespace App\Modules\Search\Support;

use App\Modules\Search\Models\SearchRegion;
use Illuminate\Support\Str;

class RegionResolver
{
    /** @var array<string, SearchRegion>|null */
    private static ?array $cache = null;

    /**
     * Clear cached lookup map.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * Resolve any raw city / region string to a canonical SearchRegion model.
     */
    public static function resolve(?string $raw): ?SearchRegion
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        self::ensureCacheLoaded();

        $norm = Str::lower($trimmed);
        if (isset(self::$cache[$norm])) {
            return self::$cache[$norm];
        }

        // Transliterate Devanagari or folded Latin to check aliases
        $fold = TransliterationSchemes::foldToLatin($trimmed);
        if (isset(self::$cache[$fold])) {
            return self::$cache[$fold];
        }

        return null;
    }

    /**
     * Preload active regions into memory map.
     */
    private static function ensureCacheLoaded(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = [];

        try {
            $regions = SearchRegion::where('is_serviceable', true)->get();
            foreach ($regions as $region) {
                // Primary English name
                $en = Str::lower($region->name_en);
                self::$cache[$en] = $region;

                // Devanagari name
                if ($region->name_dev) {
                    $dev = Str::lower($region->name_dev);
                    self::$cache[$dev] = $region;
                    $devFold = TransliterationSchemes::foldToLatin($region->name_dev);
                    self::$cache[$devFold] = $region;
                }

                // Alternate names
                if (is_array($region->name_alt_json)) {
                    foreach ($region->name_alt_json as $alt) {
                        $altStr = Str::lower(trim((string) $alt));
                        if ($altStr !== '') {
                            self::$cache[$altStr] = $region;
                            $altFold = TransliterationSchemes::foldToLatin($altStr);
                            self::$cache[$altFold] = $region;
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // In environments before migrations run
        }
    }
}
