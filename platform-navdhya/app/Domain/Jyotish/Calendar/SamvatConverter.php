<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Calendar;

final readonly class SamvatConverter
{
    /**
     * Pan-Indian Calendar Era Offsets relative to Gregorian Year (CE):
     * 1. Vikram Samvat (Chaitradi / Kartakadi): CE + 57 (or +56 depending on month transition)
     * 2. Shaka Samvat (National Calendar): CE - 78
     * 3. Kollam Era (Malayalam Calendar): CE - 825
     * 4. Bengali San (Bangabda): CE - 593
     * 5. Nepal Sambat: CE - 879
     */
    public const ERA_OFFSETS = [
        'vikram_samvat' => 57,
        'shaka_samvat' => -78,
        'kollam_era' => -825,
        'bengali_san' => -593,
        'nepal_sambat' => -879,
    ];

    /**
     * Convert a Gregorian Date to Pan-Indian Regional Eras.
     *
     * @param int $gregorianYear
     * @param int $month (1..12)
     * @param int $day (1..31)
     * @return array<string, array{era_name: string, year: int, note: string}>
     */
    public static function convert(int $gregorianYear, int $month, int $day): array
    {
        // Vikram Samvat Chaitradi begins around mid-March/April (Chaitra Shukla Pratipada)
        $isPostChaitra = ($month > 3 || ($month === 3 && $day >= 22));
        $vikramYear = $isPostChaitra ? ($gregorianYear + 57) : ($gregorianYear + 56);

        // Shaka Samvat begins on March 22 (or March 21 in leap year)
        $isPostShakaNewYear = ($month > 3 || ($month === 3 && $day >= 22));
        $shakaYear = $isPostShakaNewYear ? ($gregorianYear - 78) : ($gregorianYear - 79);

        // Bengali San begins in mid-April (Poila Boishakh ~ April 14/15)
        $isPostBoishakh = ($month > 4 || ($month === 4 && $day >= 14));
        $bengaliYear = $isPostBoishakh ? ($gregorianYear - 593) : ($gregorianYear - 594);

        // Kollam Era begins in mid-August (Chingam 1 ~ August 17)
        $isPostChingam = ($month > 8 || ($month === 8 && $day >= 17));
        $kollamYear = $isPostChingam ? ($gregorianYear - 824) : ($gregorianYear - 825);

        return [
            'vikram_samvat' => [
                'era_name' => 'Vikram Samvat (Chaitradi)',
                'year' => $vikramYear,
                'note' => 'Standard Northern and Western Hindu era',
            ],
            'shaka_samvat' => [
                'era_name' => 'Shaka Samvat (Salivahana)',
                'year' => $shakaYear,
                'note' => 'Indian National Calendar standard',
            ],
            'bengali_san' => [
                'era_name' => 'Bengali San (Bangabda)',
                'year' => $bengaliYear,
                'note' => 'Solar agricultural and cultural calendar of Bengal',
            ],
            'kollam_era' => [
                'era_name' => 'Kollam Era (Kollavarsham)',
                'year' => $kollamYear,
                'note' => 'Malayalam solar calendar era',
            ],
        ];
    }
}

