<?php

declare(strict_types=1);

namespace App\Modules\Customers\Services;

use App\Domain\Jyotish\Panchang\ChandraTaraBalaEvaluator;
use App\Domain\Jyotish\Panchang\PanchangOrchestrator;
use Carbon\CarbonImmutable;

/**
 * Devotee personalized daily Panchang and festival service (T-JYAUT-14 / JY-AUT-14).
 * Combines general civic Panchang with personalized Chandra-Tara Bala based on devotee birth Moon.
 */
final class PersonalizedDayListService
{
    /**
     * @param array{nakshatra_number: int, rashi_index: int} $devoteeProfile
     * @return array<string, mixed>
     */
    public function getPersonalizedDay(
        CarbonImmutable $date,
        float $latitude,
        float $longitude,
        array $devoteeProfile
    ): array {
        $panchang = PanchangOrchestrator::calculate(
            $date->year,
            $date->month,
            $date->day,
            $latitude,
            $longitude
        );

        $dailyMoonNakshatra = (int) ($panchang['limbs']['nakshatra']['number'] ?? 1) - 1; // 0-indexed
        $dailyMoonRashi = (int) ($panchang['moon']['rashi_index'] ?? 0);

        $chandraBala = ChandraTaraBalaEvaluator::evaluateChandraBala(
            $devoteeProfile['rashi_index'],
            $dailyMoonRashi
        );

        $taraBala = ChandraTaraBalaEvaluator::evaluateTaraBala(
            $devoteeProfile['nakshatra_number'] - 1, // 0-indexed
            $dailyMoonNakshatra
        );

        $isAuspicious = ($chandraBala['is_auspicious'] && $taraBala['is_benefic']);

        return [
            'date' => $date->toDateString(),
            'panchang' => $panchang,
            'personal_bala' => [
                'chandra_bala' => $chandraBala,
                'tara_bala' => $taraBala,
                'is_favorable' => $isAuspicious,
            ],
            'is_auspicious_for_devotee' => $isAuspicious,
        ];
    }
}
