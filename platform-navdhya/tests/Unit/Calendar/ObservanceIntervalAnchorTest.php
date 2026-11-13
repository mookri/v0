<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Festivals\ExplanationBuilder;
use App\Domain\Calendar\Festivals\FestivalRuleRegistry;
use App\Domain\Calendar\Festivals\ObservanceAnchorResolver;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ObservanceIntervalAnchorTest extends TestCase
{
    public function test_observance_anchors_pradosha_nishita_madhyahna(): void
    {
        $sunrise = CarbonImmutable::parse('2026-11-10T06:00:00Z');
        $sunset = CarbonImmutable::parse('2026-11-10T18:00:00Z');
        $nextSunrise = CarbonImmutable::parse('2026-11-11T06:00:00Z');

        // Pradosha: Sunset to Sunset + 2h 24m (18:00 to 20:24)
        $pradosha = ObservanceAnchorResolver::pradosha($sunset);
        $this->assertSame('PRADOSHA', $pradosha['name']);
        $this->assertSame('2026-11-10 18:00:00', $pradosha['starts_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-10 20:24:00', $pradosha['ends_at']->format('Y-m-d H:i:s'));

        // Nishita: Centered around midnight (00:00) with 48 min width (23:36 to 00:24)
        $nishita = ObservanceAnchorResolver::nishita($sunset, $nextSunrise);
        $this->assertSame('NISHITA', $nishita['name']);
        $this->assertSame('2026-11-10 23:36:00', $nishita['starts_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-11 00:24:00', $nishita['ends_at']->format('Y-m-d H:i:s'));

        // Madhyahna: 3rd of 5 daylight parts (10:48 to 13:12)
        $madhyahna = ObservanceAnchorResolver::madhyahna($sunrise, $sunset);
        $this->assertSame('MADHYAHNA', $madhyahna['name']);
        $this->assertSame('2026-11-10 10:48:00', $madhyahna['starts_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-10 13:12:00', $madhyahna['ends_at']->format('Y-m-d H:i:s'));
    }

    public function test_all_twelve_pilot_festivals_registered(): void
    {
        $this->assertCount(12, FestivalRuleRegistry::PILOT_FESTIVALS);

        $keys = [
            'DIWALI_LAKSHMI_PUJA',
            'MAHASHIVRATRI',
            'KRISHNA_JANMASHTAMI',
            'HOLIKA_DAHAN',
            'RANGWALI_HOLI',
            'RAM_NAVAMI',
            'GANESH_CHATURTHI',
            'KARWA_CHAUTH',
            'MAKAR_SANKRANTI',
            'GANGA_DUSSEHRA',
            'EKADASHI_DEVUTHANI',
            'AYUDHA_PUJA',
        ];

        foreach ($keys as $k) {
            $rule = FestivalRuleRegistry::getRule($k);
            $this->assertNotNull($rule, "Festival {$k} should be registered");
            $this->assertNotEmpty($rule['scripture']);
            $this->assertNotEmpty($rule['anchor']);
        }
    }

    public function test_explanation_builder_generates_human_readable_audit_trace(): void
    {
        $tithiWindow = [
            'starts_at' => CarbonImmutable::parse('2026-11-08T12:00:00Z'),
            'ends_at' => CarbonImmutable::parse('2026-11-09T14:00:00Z'),
        ];

        $anchorWindow = [
            'starts_at' => CarbonImmutable::parse('2026-11-08T12:15:00Z'),
            'ends_at' => CarbonImmutable::parse('2026-11-08T14:39:00Z'),
        ];

        $payload = ExplanationBuilder::build(
            festivalKey: 'DIWALI_LAKSHMI_PUJA',
            date: '2026-11-08',
            placeName: 'Varanasi',
            tradition: 'PURNIMANTA',
            tithiWindow: $tithiWindow,
            anchorWindow: $anchorWindow,
            alternateDateRejection: 'Amavasya ceases before Pradosha on Nov 09'
        );

        $this->assertSame('Diwali (Lakshmi Puja)', $payload['festival']);
        $this->assertSame('2026-11-08', $payload['date']);
        $this->assertStringContainsString('Varanasi', $payload['explanation']);
        $this->assertStringContainsString('PRADOSHA', $payload['explanation']);
        $this->assertStringContainsString('Dharma Sindhu', $payload['scripture_reference']);
        $this->assertNotNull($payload['alternate_date_rejection']);
    }
}

