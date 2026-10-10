<?php

namespace App\Modules\Customers;

/**
 * Email copy per notification kind (N3): the subject template registry
 * the NotificationEmail mailable renders through. Pure — no I/O.
 *
 * v1 ships English copy only; the locale parameter is accepted and
 * carried (CustomerSetting locale) so translated subjects drop in here
 * without touching the dispatcher. Unknown kinds and unknown locales
 * fall back to the generic subject — fail-closed on copy, never a raw
 * kind name in a subject line.
 */
final class NotificationEmailTemplates
{
    /**
     * @var array<string, string>
     */
    private const SUBJECTS = [
        'booking' => 'Booking update',
        'payment' => 'Payment update',
        'security' => 'Security alert',
        'support' => 'Support update',
        'system' => 'Service notice',
        'marketing' => 'Offers from Navdhya',
    ];

    public static function subject(string $kind, string $locale = 'en'): string
    {
        return self::SUBJECTS[$kind] ?? 'Notification';
    }
}
