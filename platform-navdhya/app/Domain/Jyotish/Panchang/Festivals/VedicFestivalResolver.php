<?php

namespace App\Domain\Jyotish\Panchang\Festivals;

/**
 * Vedic Festival, Vrat & Ekadashi Paran Determination Engine (T-JY05-06).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class VedicFestivalResolver
{
    /**
     * Identify festivals and observances matching the day's tithi and solar transitions.
     *
     * @param int $tithiNumber Tithi number (1..30)
     * @param string $paksha 'Shukla' or 'Krishna'
     * @param float $sunriseJd Sunrise Julian Day
     * @param float $sunsetJd Sunset Julian Day
     * @param float $tithiEndJd Tithi conclusion Julian Day
     * @return list<array{
     *     code: string,
     *     name: string,
     *     category: string,
     *     vrat: bool,
     *     description: string,
     *     paran_window?: array{start_jd: float, end_jd: float}
     * }>
     */
    public static function resolveObservances(
        int $tithiNumber,
        string $paksha,
        float $sunriseJd,
        float $sunsetJd,
        float $tithiEndJd
    ): array {
        $observances = [];

        // 1. Ekadashi Vrat (Tithi 11 or 26)
        if ($tithiNumber === 11 || $tithiNumber === 26) {
            $isShukla = ($tithiNumber === 11);
            $observances[] = [
                'code' => $isShukla ? 'shukla_ekadashi' : 'krishna_ekadashi',
                'name' => ($isShukla ? 'Shukla' : 'Krishna') . ' Ekadashi Vrat',
                'category' => 'vrat',
                'vrat' => true,
                'description' => 'Auspicious Vishnu fasting day observed from sunrise to next morning paran.',
            ];
        }

        // 2. Dwadashi Paran Window (Tithi 12 or 27)
        if ($tithiNumber === 12 || $tithiNumber === 27) {
            // Paran must be broken after sunrise, before Dwadashi ends
            $paranStart = $sunriseJd;
            $paranEnd = max($sunriseJd + (4.0 / 24.0), $tithiEndJd);

            $observances[] = [
                'code' => 'ekadashi_paran',
                'name' => 'Ekadashi Paran Window',
                'category' => 'paran',
                'vrat' => false,
                'description' => 'Optimal window to break the Ekadashi fast after sunrise.',
                'paran_window' => [
                    'start_jd' => $paranStart,
                    'end_jd' => $paranEnd,
                ],
            ];
        }

        // 3. Pradosh Vrat (Tithi 13 or 28)
        if ($tithiNumber === 13 || $tithiNumber === 28) {
            $observances[] = [
                'code' => 'pradosh_vrat',
                'name' => 'Pradosh Vrat (Shiva Puja)',
                'category' => 'vrat',
                'vrat' => true,
                'description' => 'Sacred evening twilight window dedicated to Lord Shiva and Parvati.',
            ];
        }

        // 4. Purnima (Tithi 15)
        if ($tithiNumber === 15) {
            $observances[] = [
                'code' => 'purnima_vrat',
                'name' => 'Purnima Vrat & Satyanarayan Puja',
                'category' => 'puja',
                'vrat' => true,
                'description' => 'Full Moon day worship for abundance, prosperity, and truth.',
            ];
        }

        // 5. Amavasya (Tithi 30)
        if ($tithiNumber === 30) {
            $observances[] = [
                'code' => 'amavasya_pitru_tarpan',
                'name' => 'Amavasya Pitru Tarpan',
                'category' => 'tarpan',
                'vrat' => false,
                'description' => 'New Moon day ancestral remembrance and charity offerings.',
            ];
        }

        // 6. Vinayaka / Sankashti Chaturthi (Tithi 4 or 19)
        if ($tithiNumber === 4) {
            $observances[] = [
                'code' => 'vinayaka_chaturthi',
                'name' => 'Vinayaka Chaturthi',
                'category' => 'vrat',
                'vrat' => true,
                'description' => 'Monthly Ganesh fasting for removal of obstacles.',
            ];
        } elseif ($tithiNumber === 19) {
            $observances[] = [
                'code' => 'sankashti_chaturthi',
                'name' => 'Sankashti Chaturthi',
                'category' => 'vrat',
                'vrat' => true,
                'description' => 'Evening moonrise fasting dedicated to Lord Ganesha.',
            ];
        }

        return $observances;
    }
}

