<?php

namespace App\Domain\Calendar\Support;

use Carbon\CarbonImmutable;

/**
 * RFC 5545 iCalendar (.ics) Generator for Vedic Panchang and Festivals.
 * Fulfills §18 and Gate G-UX-1.
 */
final class IcsCalendarExporter
{
    /**
     * @param string $cityName
     * @param string $profileCode
     * @param string $timezone
     * @param list<array{
     *     uid: string,
     *     title: string,
     *     description: string,
     *     starts_at: CarbonImmutable,
     *     ends_at: CarbonImmutable
     * }> $events
     * @return string Valid RFC 5545 iCalendar payload
     */
    public static function export(
        string $cityName,
        string $profileCode,
        string $timezone,
        array $events
    ): string {
        $now = CarbonImmutable::now('UTC')->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Navdhya Sacred Ember//Panchang Engine v3//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            "X-WR-CALNAME:Navdhya Panchang - {$cityName}",
            "X-WR-TIMEZONE:{$timezone}",
        ];

        foreach ($events as $event) {
            $dtStart = $event['starts_at']->setTimezone('UTC')->format('Ymd\THis\Z');
            $dtEnd = $event['ends_at']->setTimezone('UTC')->format('Ymd\THis\Z');
            $desc = self::escapeIcsText($event['description'] . " [Profile: {$profileCode}]");
            $summary = self::escapeIcsText($event['title']);

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = "UID:{$event['uid']}@navdhya.com";
            $lines[] = "DTSTAMP:{$now}";
            $lines[] = "DTSTART:{$dtStart}";
            $lines[] = "DTEND:{$dtEnd}";
            $lines[] = "SUMMARY:{$summary}";
            $lines[] = "DESCRIPTION:{$desc}";
            $lines[] = 'STATUS:CONFIRMED';
            $lines[] = 'TRANSP:OPAQUE';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    private static function escapeIcsText(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', ''],
            $text
        );
    }
}

