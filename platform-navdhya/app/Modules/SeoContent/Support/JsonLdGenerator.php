<?php

namespace App\Modules\SeoContent\Support;

use Carbon\CarbonImmutable;

/**
 * High-integrity Schema.org JSON-LD generator.
 * Enforces truthful dateModified (P-12) and strict canonical normalization.
 */
final class JsonLdGenerator
{
    /**
     * Generate BreadcrumbList JSON-LD.
     *
     * @param list<array{name: string, url: string}> $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [];
        $pos = 1;

        foreach ($crumbs as $c) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $c['name'],
                'item' => strtolower(rtrim($c['url'], '/')),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Generate Festival Event JSON-LD with truthful dateModified.
     *
     * @param string $name
     * @param string $description
     * @param string $canonicalUrl
     * @param CarbonImmutable $startDate
     * @param CarbonImmutable $endDate
     * @param string $placeName
     * @param CarbonImmutable $truthfulDateModified
     * @return array<string, mixed>
     */
    public static function festivalEvent(
        string $name,
        string $description,
        string $canonicalUrl,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        string $placeName,
        CarbonImmutable $truthfulDateModified
    ): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $name,
            'description' => $description,
            'url' => strtolower(rtrim($canonicalUrl, '/')),
            'startDate' => $startDate->toIso8601String(),
            'endDate' => $endDate->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => [
                '@type' => 'Place',
                'name' => $placeName,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $placeName,
                    'addressCountry' => 'IN',
                ],
            ],
            'organizer' => [
                '@type' => 'Organization',
                'name' => 'Navdhya Sacred Ember',
                'url' => 'https://navdhya.com',
            ],
            // Truthful dateModified (Closes P-12)
            'dateModified' => $truthfulDateModified->toIso8601String(),
        ];
    }
}

