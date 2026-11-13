<?php

namespace App\Domain\Jyotish\Panchang;

use App\Domain\Jyotish\Astronomy\AscendantSolver;
use App\Domain\Jyotish\Astronomy\AyanamsaEngine;
use App\Domain\Jyotish\Astronomy\SiderealLayer;
use App\Domain\Jyotish\Astronomy\TopocentricRiseSetCalculator;
use App\Domain\Jyotish\Astronomy\TransitionSolver;
use App\Domain\Jyotish\Chart\BoundaryIndexer;
use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Ephemeris\EphemerisProviderInterface;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;
use App\Domain\Jyotish\Support\Angle;

/**
 * Integrated Five-Limb Panchang Orchestrator (T-JY05-01).
 *
 * Computes:
 * 1. Tithi (1..30 with Paksha and start/end transitions)
 * 2. Vara (Vedic sunrise-anchored weekday)
 * 3. Nakshatra (1..27 with Pada and sound syllable)
 * 4. Yoga (1..27 Luni-Solar sum)
 * 5. Karana (1..60 half-tithi with Vishti/Bhadra flag)
 * 6. Topocentric Sun transitions (Sunrise, Sunset, Solar Noon)
 * 7. Daylight octiles (Rahu Kaal, Yamaghanta, Gulika Kaal) and Day Choghadiyas
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class PanchangOrchestrator implements ZodiacConstants
{
    public const TITHI_NAMES = [
        1 => 'Pratipada', 2 => 'Dwitiya', 3 => 'Tritiya', 4 => 'Chaturthi', 5 => 'Panchami',
        6 => 'Shashthi', 7 => 'Saptami', 8 => 'Ashtami', 9 => 'Navami', 10 => 'Dashami',
        11 => 'Ekadashi', 12 => 'Dwadashi', 13 => 'Trayodashi', 14 => 'Chaturdashi', 15 => 'Purnima',
        16 => 'Pratipada', 17 => 'Dwitiya', 18 => 'Tritiya', 19 => 'Chaturthi', 20 => 'Panchami',
        21 => 'Shashthi', 22 => 'Saptami', 23 => 'Ashtami', 24 => 'Navami', 25 => 'Dashami',
        26 => 'Ekadashi', 27 => 'Dwadashi', 28 => 'Trayodashi', 29 => 'Chaturdashi', 30 => 'Amavasya',
    ];

    public const YOGA_NAMES = [
        1 => 'Vishkumbha', 2 => 'Priti', 3 => 'Ayushman', 4 => 'Saubhagya', 5 => 'Shobhana',
        6 => 'Atiganda', 7 => 'Sukarma', 8 => 'Dhriti', 9 => 'Shula', 10 => 'Ganda',
        11 => 'Vriddhi', 12 => 'Dhruva', 13 => 'Vyaghata', 14 => 'Harshana', 15 => 'Vajra',
        16 => 'Siddhi', 17 => 'Vyatipata', 18 => 'Variyan', 19 => 'Parigha', 20 => 'Shiva',
        21 => 'Siddha', 22 => 'Sadhya', 23 => 'Shubha', 24 => 'Shukla', 25 => 'Brahma',
        26 => 'Indra', 27 => 'Vaidhriti',
    ];

    public const VARA_NAMES = [
        0 => ['name' => 'Ravivara', 'english' => 'Sunday', 'ruling_graha' => 'sun'],
        1 => ['name' => 'Somavara', 'english' => 'Monday', 'ruling_graha' => 'moon'],
        2 => ['name' => 'Mangalavara', 'english' => 'Tuesday', 'ruling_graha' => 'mars'],
        3 => ['name' => 'Budhavara', 'english' => 'Wednesday', 'ruling_graha' => 'mercury'],
        4 => ['name' => 'Guruvara', 'english' => 'Thursday', 'ruling_graha' => 'jupiter'],
        5 => ['name' => 'Shukravara', 'english' => 'Friday', 'ruling_graha' => 'venus'],
        6 => ['name' => 'Shanivara', 'english' => 'Saturday', 'ruling_graha' => 'saturn'],
    ];

    public const FIXED_KARANAS = [
        1 => 'Kimstughna', 58 => 'Shakuni', 59 => 'Chatushpada', 60 => 'Naga',
    ];

    public const MOVABLE_KARANAS = [
        0 => 'Bava', 1 => 'Balava', 2 => 'Kaulava', 3 => 'Taitila', 4 => 'Gara', 5 => 'Vanija', 6 => 'Vishti',
    ];

    /**
     * Compute full Panchang for a given civil date, instant, and location.
     *
     * @param float $jdUt1 Julian Day UT1
     * @param float $jdTt Julian Day TT
     * @param float $latitude Geographic latitude
     * @param float $longitude Geographic longitude
     * @param float $elevationMeters Elevation above sea level
     * @param string $ayanamsaSystem Ayanamsa code
     * @param EphemerisProviderInterface|null $provider
     * @return array<string, mixed>
     */
    public static function calculate(
        float $jdUt1,
        float $jdTt,
        float $latitude,
        float $longitude,
        float $elevationMeters = 0.0,
        string $ayanamsaSystem = AyanamsaEngine::SYSTEM_LAHIRI,
        ?EphemerisProviderInterface $provider = null
    ): array {
        $ephem = $provider ?? new MeeusSeriesProvider();

        // 1. Ephemeris & Sidereal positions
        $tropicalSnap = $ephem->computeSnapshot($jdTt, $jdUt1);
        $conversion = SiderealLayer::convert($tropicalSnap, $ayanamsaSystem);
        $siderealSnap = $conversion['snapshot'];
        $ayanamsaMeta = $conversion['ayanamsa'];
        $ayanamsaDeg = $ayanamsaMeta['ayanamsa_degrees'];

        $sunSidereal = $siderealSnap->getGrahaPositions()['sun']['longitude'];
        $moonSidereal = $siderealSnap->getGrahaPositions()['moon']['longitude'];

        // 2. Solar Horizon (Sunrise / Sunset / Noon)
        $jdMidnight = floor($jdUt1 - 0.5) + 0.5;
        $sunHorizon = TopocentricRiseSetCalculator::calculateSun(
            $jdMidnight,
            $latitude,
            $longitude,
            $elevationMeters,
            $ephem
        );

        $sunriseJd = $sunHorizon['sunrise_jd'] ?? ($jdMidnight + 0.25);
        $sunsetJd = $sunHorizon['sunset_jd'] ?? ($jdMidnight + 0.75);

        // 3. Vara (Vedic Weekday)
        // JD at noon UTC gives weekday index: (floor(jd + 1.5) % 7)
        // If query instant is before sunrise, Vedic day belongs to previous cycle
        $rawDayIndex = (int) floor($jdUt1 + 1.5) % 7;
        $isPreSunrise = ($jdUt1 < $sunriseJd);
        $vedicVaraIndex = $isPreSunrise ? (($rawDayIndex + 6) % 7) : $rawDayIndex;
        $varaMeta = self::VARA_NAMES[$vedicVaraIndex];

        // 4. Tithi (1..30)
        $elongation = Angle::normalize($moonSidereal - $sunSidereal);
        $tithiNum = (int) floor($elongation / 12.0) + 1;
        if ($tithiNum > 30) {
            $tithiNum = 30;
        }
        $paksha = ($tithiNum <= 15) ? 'Shukla' : 'Krishna';
        $tithiName = self::TITHI_NAMES[$tithiNum];
        $degInTithi = fmod($elongation, 12.0);
        $tithiProgress = $degInTithi / 12.0;

        // Transition: Tithi end JD
        $tithiEndJd = TransitionSolver::resolveTithiEndJd($jdTt, $tithiNum, $ephem);

        // 5. Nakshatra (1..27)
        $nakshatraBoundary = BoundaryIndexer::index($moonSidereal);
        $nakshatraIndex = $nakshatraBoundary['nakshatra']['index'];
        $nakshatraEndJd = TransitionSolver::resolveNakshatraEndJd($jdTt, $nakshatraIndex, $ayanamsaDeg, $ephem);

        // 6. Yoga (1..27)
        $yogaSum = Angle::normalize($sunSidereal + $moonSidereal);
        $yogaSpan = 360.0 / 27.0;
        $yogaNum = (int) floor($yogaSum / $yogaSpan) + 1;
        if ($yogaNum > 27) {
            $yogaNum = 27;
        }
        $yogaName = self::YOGA_NAMES[$yogaNum];
        $yogaProgress = fmod($yogaSum, $yogaSpan) / $yogaSpan;

        // 7. Karana (1..60)
        $karanaHalfTithi = (int) floor($elongation / 6.0) + 1;
        if ($karanaHalfTithi > 60) {
            $karanaHalfTithi = 60;
        }
        $isFixedKarana = isset(self::FIXED_KARANAS[$karanaHalfTithi]);
        if ($isFixedKarana) {
            $karanaName = self::FIXED_KARANAS[$karanaHalfTithi];
        } else {
            $movableIndex = ($karanaHalfTithi - 2) % 7;
            $karanaName = self::MOVABLE_KARANAS[$movableIndex];
        }
        $isVishti = ($karanaName === 'Vishti');
        $karanaProgress = fmod($elongation, 6.0) / 6.0;

        // 8. Day divisions (Rahu Kaal, Yamaghanta, Gulika Kaal, Choghadiya)
        $dayDivisions = DayDivisionCalculator::calculateDaytimeDivisions(
            $sunriseJd,
            $sunsetJd,
            $vedicVaraIndex
        );

        return [
            'calendar_date_jd' => $jdUt1,
            'ayanamsa' => $ayanamsaMeta,
            'solar_horizon' => $sunHorizon,
            'limbs' => [
                'tithi' => [
                    'number' => $tithiNum,
                    'name' => $tithiName,
                    'paksha' => $paksha,
                    'index_in_paksha' => ($tithiNum <= 15) ? $tithiNum : ($tithiNum - 15),
                    'elongation_degrees' => round($elongation, 4),
                    'progress_ratio' => round($tithiProgress, 4),
                    'end_jd' => $tithiEndJd,
                ],
                'vara' => [
                    'index' => $vedicVaraIndex,
                    'name' => $varaMeta['name'],
                    'english' => $varaMeta['english'],
                    'lord' => $varaMeta['ruling_graha'],
                    'is_pre_sunrise' => $isPreSunrise,
                ],
                'nakshatra' => [
                    'index' => $nakshatraIndex,
                    'number' => $nakshatraIndex + 1,
                    'name' => $nakshatraBoundary['nakshatra']['name'],
                    'lord' => $nakshatraBoundary['nakshatra']['lord'],
                    'pada' => $nakshatraBoundary['nakshatra']['pada'],
                    'syllable' => $nakshatraBoundary['nakshatra']['syllable'],
                    'moon_longitude' => round($moonSidereal, 4),
                    'progress_ratio' => round($nakshatraBoundary['nakshatra']['elapsed_fraction'], 4),
                    'end_jd' => $nakshatraEndJd,
                ],
                'yoga' => [
                    'number' => $yogaNum,
                    'name' => $yogaName,
                    'luni_solar_sum_degrees' => round($yogaSum, 4),
                    'progress_ratio' => round($yogaProgress, 4),
                ],
                'karana' => [
                    'half_tithi' => $karanaHalfTithi,
                    'name' => $karanaName,
                    'is_fixed' => $isFixedKarana,
                    'is_vishti_bhadra' => $isVishti,
                    'progress_ratio' => round($karanaProgress, 4),
                ],
            ],
            'inauspicious_windows' => [
                'rahu_kaal' => $dayDivisions['rahu_kaal'],
                'yamaghanta' => $dayDivisions['yamaghanta'],
                'gulika_kaal' => $dayDivisions['gulika_kaal'],
            ],
            'day_choghadiya' => $dayDivisions['choghadiya'],
        ];
    }
}

