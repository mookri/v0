<?php

namespace App\Modules\Customers\Telemetry;

/**
 * Event registry seeded from packages/analytics/src/registry/baseline.json (E5).
 * Enforces one registry across both TypeScript client collectors and PHP ingestion planes.
 */
final class EventRegistry
{
    /** @var list<string> The 15 baseline analytics events plus customer facts. */
    public const REGISTERED_EVENTS = [
        'ad_click',
        'ad_impression',
        'app_error',
        'begin_checkout',
        'booking_confirmed',
        'checkout_started',
        'kyc_submitted',
        'login',
        'page_view',
        'payment_failed',
        'payment_succeeded',
        'purchase_viewed',
        'search',
        'sign_up',
        'view_item',
        'zero_results_search',
        // Customer domain extensions
        'reminder_viewed',
        'spiritual_profile_updated',
        'family_member_updated',
    ];

    public static function isValid(string $eventName): bool
    {
        return in_array($eventName, self::REGISTERED_EVENTS, true);
    }
}
