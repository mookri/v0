<?php

namespace Tests\Feature\Calendar;

use App\Domain\Calendar\Support\IcsCalendarExporter;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class IcsExportTest extends TestCase
{
    public function test_ics_export_generates_valid_rfc_5545_payload(): void
    {
        $events = [
            [
                'uid' => 'fest_diwali_2026',
                'title' => 'Diwali (Lakshmi Puja)',
                'description' => 'Lakshmi Puja observed during Pradosha Kaal.',
                'starts_at' => CarbonImmutable::parse('2026-11-08T18:00:00Z'),
                'ends_at' => CarbonImmutable::parse('2026-11-08T20:24:00Z'),
            ],
            [
                'uid' => 'fest_shivratri_2027',
                'title' => 'Maha Shivratri',
                'description' => 'Nishita Kaal Puja on Krishna Chaturdashi.',
                'starts_at' => CarbonImmutable::parse('2027-03-06T23:36:00Z'),
                'ends_at' => CarbonImmutable::parse('2027-03-07T00:24:00Z'),
            ],
        ];

        $ics = IcsCalendarExporter::export(
            cityName: 'Varanasi',
            profileCode: 'drik-lahiri-amanta',
            timezone: 'Asia/Kolkata',
            events: $events
        );

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString('VERSION:2.0', $ics);
        $this->assertStringContainsString('PRODID:-//Navdhya Sacred Ember//Panchang Engine v3//EN', $ics);
        $this->assertStringContainsString('X-WR-CALNAME:Navdhya Panchang - Varanasi', $ics);
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('UID:fest_diwali_2026@navdhya.com', $ics);
        $this->assertStringContainsString('SUMMARY:Diwali (Lakshmi Puja)', $ics);
        $this->assertStringContainsString('[Profile: drik-lahiri-amanta]', $ics);
        $this->assertStringContainsString('DTSTART:20261108T180000Z', $ics);
        $this->assertStringContainsString('DTEND:20261108T202400Z', $ics);
        $this->assertStringContainsString('STATUS:CONFIRMED', $ics);
    }
}

