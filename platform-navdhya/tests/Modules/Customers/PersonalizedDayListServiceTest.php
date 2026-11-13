<?php

declare(strict_types=1);

namespace Tests\Modules\Customers;

use App\Modules\Customers\Services\PersonalizedDayListService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Validates personalized day list generation (T-JYAUT-14).
 */
final class PersonalizedDayListServiceTest extends TestCase
{
    public function test_personal_bala_is_evaluated_for_devotee(): void
    {
        $service = new PersonalizedDayListService();
        $date = CarbonImmutable::parse('2026-04-14', 'Asia/Kolkata');

        $result = $service->getPersonalizedDay($date, 23.1765, 75.7885, [
            'nakshatra_number' => 1, // Ashwini
            'rashi_index' => 0,      // Mesha
        ]);

        $this->assertSame('2026-04-14', $result['date']);
        $this->assertArrayHasKey('panchang', $result);
        $this->assertArrayHasKey('personal_bala', $result);
        $this->assertIsBool($result['is_auspicious_for_devotee']);
    }
}

