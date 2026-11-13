<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Muhurat;

use App\Domain\Jyotish\Astronomy\HorizonStatusEnum;
use App\Domain\Jyotish\Astronomy\Time\AstronomicalTime;
use App\Domain\Jyotish\Astronomy\TopocentricRiseSetCalculator;
use App\Domain\Jyotish\Muhurat\Models\MuhuratCandidate;
use App\Domain\Jyotish\Muhurat\Rules\OccasionRuleRegistry;
use App\Domain\Jyotish\Muhurat\Scoring\MuhuratScorer;
use App\Domain\Jyotish\Panchang\DayDivisionCalculator;
use App\Domain\Jyotish\Panchang\PanchangOrchestrator;
use Illuminate\Support\Str;

final readonly class IntervalSearchEngine
{
    public function __construct(
        private OccasionRuleRegistry $ruleRegistry = new OccasionRuleRegistry(),
        private MuhuratScorer $scorer = new MuhuratScorer(),
    ) {
    }

    /**
     * Search electional Muhurats across a date interval.
     * Evaluates boundaries per civil day (O(days)) rather than per second polling (O(seconds)).
     *
     * @return list<MuhuratCandidate>
     */
    public function search(
        string $occasionCode,
        \DateTimeImmutable $startDate,
        int $daysCount,
        float $latitude,
        float $longitude,
        string $timezone = 'Asia/Kolkata',
    ): array {
        $rule = $this->ruleRegistry->get($occasionCode);
        if ($rule === null) {
            throw new \InvalidArgumentException("Unsupported occasion code: {$occasionCode}");
        }

        $candidates = [];

        for ($i = 0; $i < $daysCount; $i++) {
            $currentDate = $startDate->modify("+{$i} days");
            $year = (int) $currentDate->format('Y');
            $month = (int) $currentDate->format('m');
            $day = (int) $currentDate->format('d');

            $jdMidnight = AstronomicalTime::calculateJulianDay($year, $month, $day, 0, 0, 0);
            $jdNoon = AstronomicalTime::calculateJulianDay($year, $month, $day, 6, 0, 0);

            // 1. Compute Panchang Limbs
            $panchang = PanchangOrchestrator::calculate(
                jdUt1: $jdNoon,
                jdTt: $jdNoon + (69.184 / 86400.0),
                latitude: $latitude,
                longitude: $longitude,
            );

            // 2. Solar horizon & Day divisions (Rahu Kaal, Yamaghanta, Abhijit)
            $sunHorizon = TopocentricRiseSetCalculator::calculateSun($jdMidnight, $latitude, $longitude);
            if ($sunHorizon['status'] !== HorizonStatusEnum::NORMAL || $sunHorizon['sunrise_jd'] === null || $sunHorizon['sunset_jd'] === null) {
                continue;
            }

            $sunriseJd = $sunHorizon['sunrise_jd'];
            $sunsetJd = $sunHorizon['sunset_jd'];
            $varaNumber = $panchang['limbs']['vara']['index'];
            $divisions = DayDivisionCalculator::calculateDaytimeDivisions($sunriseJd, $sunsetJd, $varaNumber);

            // 3. Formulate Candidate Window 1: Morning Auspicious Window (Post-Sunrise Octiles 2 & 3)
            $middayStartJd = $sunriseJd + (($sunsetJd - $sunriseJd) * 0.25);
            $middayEndJd = $sunriseJd + (($sunsetJd - $sunriseJd) * 0.50);

            $rahuStart = $divisions['rahu_kaal']['start_jd'];
            $rahuEnd = $divisions['rahu_kaal']['end_jd'];
            $inRahuKaal = ($middayStartJd < $rahuEnd && $middayEndJd > $rahuStart);

            $context = [
                'tithi_number' => $panchang['limbs']['tithi']['number'],
                'nakshatra_number' => $panchang['limbs']['nakshatra']['number'],
                'vara_number' => $panchang['limbs']['vara']['index'],
                'yoga_number' => $panchang['limbs']['yoga']['number'],
                'karana_number' => $panchang['limbs']['karana']['half_tithi'],
                'in_rahu_kaal' => $inRahuKaal,
                'choghadiya_auspicious' => true,
            ];

            $scored = $this->scorer->scoreCandidate($rule, $context);

            $startsAtUtc = self::jdToDateTime($middayStartJd);
            $endsAtUtc = self::jdToDateTime($middayEndJd);

            if (!$scored['is_disqualified'] && $scored['score'] >= 50.0) {
                $candidates[] = new MuhuratCandidate(
                    candidateId: (string) Str::uuid(),
                    occasionCode: $rule->getOccasionCode(),
                    startsAt: $startsAtUtc,
                    endsAt: $endsAtUtc,
                    score: $scored['score'],
                    isDisqualified: false,
                    reasons: $scored['reasons'],
                    exclusions: $scored['exclusions'],
                    planetarySnapshot: [
                        'tithi' => $panchang['limbs']['tithi']['name'],
                        'nakshatra' => $panchang['limbs']['nakshatra']['name'],
                        'vara' => $panchang['limbs']['vara']['name'],
                    ],
                );
            }

            // 4. Formulate Candidate Window 2: Abhijit Muhurat Window (Midday ~24 mins before/after noon)
            $solarNoonJd = $sunHorizon['solar_noon_jd'];
            $abhijitSpanJd = (24.0 / 1440.0); // 24 minutes in days
            $abhijitStartJd = $solarNoonJd - $abhijitSpanJd;
            $abhijitEndJd = $solarNoonJd + $abhijitSpanJd;

            $isWednesday = ($varaNumber === 3);
            $inRahuKaalAbhijit = ($abhijitStartJd < $rahuEnd && $abhijitEndJd > $rahuStart);

            $abhijitContext = [
                'tithi_number' => $panchang['limbs']['tithi']['number'],
                'nakshatra_number' => $panchang['limbs']['nakshatra']['number'],
                'vara_number' => $panchang['limbs']['vara']['index'],
                'in_rahu_kaal' => $inRahuKaalAbhijit,
                'is_abhijit' => !$isWednesday,
                'choghadiya_auspicious' => true,
            ];

            $abhijitScored = $this->scorer->scoreCandidate($rule, $abhijitContext);

            if (!$abhijitScored['is_disqualified'] && $abhijitScored['score'] >= 50.0) {
                $candidates[] = new MuhuratCandidate(
                    candidateId: (string) Str::uuid(),
                    occasionCode: $rule->getOccasionCode(),
                    startsAt: self::jdToDateTime($abhijitStartJd),
                    endsAt: self::jdToDateTime($abhijitEndJd),
                    score: $abhijitScored['score'],
                    isDisqualified: false,
                    reasons: $abhijitScored['reasons'],
                    exclusions: $abhijitScored['exclusions'],
                    planetarySnapshot: [
                        'window' => 'ABHIJIT_MUHURAT',
                        'tithi' => $panchang['limbs']['tithi']['name'],
                        'nakshatra' => $panchang['limbs']['nakshatra']['name'],
                    ],
                );
            }
        }

        // Sort descending by score
        usort($candidates, static fn (MuhuratCandidate $a, MuhuratCandidate $b) => $b->score <=> $a->score);

        return $candidates;
    }

    private static function jdToDateTime(float $jd): \DateTimeImmutable
    {
        // JD to Unix epoch timestamp: (JD - 2440587.5) * 86400
        $timestamp = (int) round(($jd - 2440587.5) * 86400.0);
        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('UTC'));
    }
}
