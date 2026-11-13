<?php

namespace App\Modules\Recommendations\Services;

class ReasonCodeGenerator
{
    public const RECENT_SEARCH = 'RECENT_SEARCH';
    public const RECENT_BOOKING = 'RECENT_BOOKING';
    public const SAME_TRADITION = 'SAME_TRADITION';
    public const SAME_SHAKHA = 'SAME_SHAKHA';
    public const PANDIT_QUALIFIED_FOR_SKU = 'PANDIT_QUALIFIED_FOR_SKU';
    public const NEARBY_VERIFIED_TEMPLE = 'NEARBY_VERIFIED_TEMPLE';
    public const POPULAR_IN_CATEGORY = 'POPULAR_IN_CATEGORY';
    public const POPULAR_NEAR_YOU = 'POPULAR_NEAR_YOU';

    private const VERIFIED_REASONS = [
        self::RECENT_SEARCH => 'Based on your recent search',
        self::RECENT_BOOKING => 'Recommended from your previous booking',
        self::SAME_TRADITION => 'Aligns with your family tradition',
        self::SAME_SHAKHA => 'Matches your Vedic Shakha',
        self::PANDIT_QUALIFIED_FOR_SKU => 'Verified Pandit qualified for this ritual',
        self::NEARBY_VERIFIED_TEMPLE => 'Sacred verified temple near your location',
        self::POPULAR_IN_CATEGORY => 'Frequently booked in this category',
        self::POPULAR_NEAR_YOU => 'Popular among devotees in your city',
    ];

    /**
     * Resolve user-facing label for a verified reason code.
     */
    public function getLabel(string $code): string
    {
        if (! isset(self::VERIFIED_REASONS[$code])) {
            throw new \InvalidArgumentException("Reason code '{$code}' is outside the verified explainability vocabulary.");
        }

        return self::VERIFIED_REASONS[$code];
    }

    /**
     * Determine best verifiable reason code for candidate context.
     *
     * @param array<string, mixed> $context
     */
    public function determineReason(array $context): string
    {
        if (! empty($context['same_tradition'])) {
            return self::SAME_TRADITION;
        }

        if (! empty($context['same_shakha'])) {
            return self::SAME_SHAKHA;
        }

        if (! empty($context['recent_booking'])) {
            return self::RECENT_BOOKING;
        }

        if (! empty($context['recent_search'])) {
            return self::RECENT_SEARCH;
        }

        if (! empty($context['pandit_qualified'])) {
            return self::PANDIT_QUALIFIED_FOR_SKU;
        }

        if (! empty($context['nearby_temple'])) {
            return self::NEARBY_VERIFIED_TEMPLE;
        }

        if (! empty($context['city_matched'])) {
            return self::POPULAR_NEAR_YOU;
        }

        return self::POPULAR_IN_CATEGORY;
    }
}

