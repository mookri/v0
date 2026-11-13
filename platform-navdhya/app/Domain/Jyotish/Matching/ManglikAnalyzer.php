<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Matching;

final readonly class ManglikAnalyzer
{
    /**
     * Houses where Mars generates Kuja Dosha (1, 2, 4, 7, 8, 12).
     */
    public const MANGLIK_HOUSES = [1, 2, 4, 7, 8, 12];

    /**
     * Evaluate Mars placements from Lagna, Moon, and Venus independently.
     * Applies classical Parashari cancellation exceptions.
     *
     * @param int $marsRashi (1..12)
     * @param int $lagnaRashi (1..12)
     * @param int $moonRashi (1..12)
     * @param int $venusRashi (1..12)
     * @param bool $isJupiterAspectingMars
     * @return array{
     *     is_manglik: bool,
     *     mars_houses: array{from_lagna: int, from_moon: int, from_venus: int},
     *     is_cancelled: bool,
     *     cancellation_reasons: list<string>
     * }
     */
    public function analyze(
        int $marsRashi,
        int $lagnaRashi,
        int $moonRashi,
        int $venusRashi,
        bool $isJupiterAspectingMars = false
    ): array {
        $houseFromLagna = (($marsRashi - $lagnaRashi + 12) % 12) + 1;
        $houseFromMoon = (($marsRashi - $moonRashi + 12) % 12) + 1;
        $houseFromVenus = (($marsRashi - $venusRashi + 12) % 12) + 1;

        $isManglikLagna = in_array($houseFromLagna, self::MANGLIK_HOUSES, true);
        $isManglikMoon = in_array($houseFromMoon, self::MANGLIK_HOUSES, true);
        $isManglikVenus = in_array($houseFromVenus, self::MANGLIK_HOUSES, true);

        $isRawManglik = ($isManglikLagna || $isManglikMoon || $isManglikVenus);
        $cancellationReasons = [];

        // Exception 1: Mars in own sign (Aries 1, Scorpio 8)
        if ($marsRashi === 1 || $marsRashi === 8) {
            $cancellationReasons[] = 'Mars is in its own swakshetra (Aries/Scorpio), nullifying adverse effects.';
        }

        // Exception 2: Mars exalted in Capricorn (10)
        if ($marsRashi === 10) {
            $cancellationReasons[] = 'Mars is in deep exaltation (Capricorn), converting Kuja dosha into constructive vigor.';
        }

        // Exception 3: Benefic Jupiter aspect
        if ($isJupiterAspectingMars) {
            $cancellationReasons[] = 'Guru Drishti (Jupiter aspect) directly pacifies and neutralizes Kuja Dosha.';
        }

        // Exception 4: Mars in 2nd house in Gemini or Virgo (Budha signs)
        if ($houseFromLagna === 2 && ($marsRashi === 3 || $marsRashi === 6)) {
            $cancellationReasons[] = 'Mars in 2nd house in Mercury signs cancels Kuja dosha.';
        }

        $isCancelled = !empty($cancellationReasons);
        $finalManglik = $isRawManglik && !$isCancelled;

        return [
            'is_manglik' => $finalManglik,
            'is_raw_manglik' => $isRawManglik,
            'mars_houses' => [
                'from_lagna' => $houseFromLagna,
                'from_moon' => $houseFromMoon,
                'from_venus' => $houseFromVenus,
            ],
            'is_cancelled' => $isCancelled,
            'cancellation_reasons' => $cancellationReasons,
        ];
    }
}

