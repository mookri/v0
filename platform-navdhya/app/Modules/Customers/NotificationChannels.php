<?php

namespace App\Modules\Customers;

use Carbon\CarbonImmutable;

/**
 * Notification routing policy (N1) — the PHP port of the normative
 * semantics in packages/communication (selectChannel quiet-hours
 * handling, dedupKey identity). Pure functions over already-resolved
 * inputs; no I/O, no ambient state — the dispatcher job consults it.
 *
 * N1 executes the in_app leg; N3 executes email through the mailable +
 * per-channel claim row (roadmap posture: in_app + email first). Sms is
 * ROUTED (preference + quiet-hours decisions are real and tested) but
 * delivered only when its provider track lands — the matrix never
 * changes, only the executors grow.
 */
final class NotificationChannels
{
    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CATEGORY_TRANSACTIONAL = 'transactional';

    public const CATEGORY_SECURITY = 'security';

    public const CATEGORY_SERVICE = 'service';

    public const CATEGORY_MARKETING = 'marketing';

    /**
     * Notification kind => communication category (unknown kinds are
     * transactional — service facts, never marketing).
     *
     * @var array<string, string>
     */
    private const KIND_CATEGORIES = [
        'booking' => self::CATEGORY_TRANSACTIONAL,
        'payment' => self::CATEGORY_TRANSACTIONAL,
        'security' => self::CATEGORY_SECURITY,
        'support' => self::CATEGORY_SERVICE,
        'system' => self::CATEGORY_SERVICE,
        'marketing' => self::CATEGORY_MARKETING,
    ];

    public static function categoryFor(string $kind): string
    {
        return self::KIND_CATEGORIES[$kind] ?? self::CATEGORY_TRANSACTIONAL;
    }

    /**
     * The channels this notification is routed to (spec matrix):
     * - in_app ALWAYS (N1 posture — the passive inbox is quiet-hours-
     *   safe and preference-independent);
     * - email iff the notify.email flag AND the notifications_email
     *   preference allow it;
     * - sms iff the notifications_sms preference allows it (no provider
     *   yet — routed, never delivered, until the SMS track);
     * - quiet hours suppress service/marketing outbound legs;
     *   transactional/security bypass (packages/communication).
     *
     * @param  array<string, mixed>  $settings  resolved CustomerSetting values (key => value)
     * @return list<string>
     */
    public static function routed(string $kind, array $settings, bool $emailEnabled, bool $quietHoursActive): array
    {
        $category = self::categoryFor($kind);
        $channels = [self::CHANNEL_IN_APP];

        $suppress = $quietHoursActive
            && in_array($category, [self::CATEGORY_SERVICE, self::CATEGORY_MARKETING], true);

        if ($suppress) {
            return $channels;
        }

        if ($emailEnabled && ($settings['notifications_email'] ?? true) !== false) {
            $channels[] = self::CHANNEL_EMAIL;
        }

        if (($settings['notifications_sms'] ?? true) !== false) {
            $channels[] = self::CHANNEL_SMS;
        }

        return $channels;
    }

    /**
     * Dedup identity (packages/communication dedupKey):
     * rule:event:recipient:channel. The dispatcher composes it; the
     * caller only supplies the event identity (N2 passes outbox event
     * ids). Null eventId = no dedup (one-shot facts).
     */
    public static function dedupKey(string $kind, string $eventId, string $userId, string $channel): string
    {
        return "{$kind}:{$eventId}:{$userId}:{$channel}";
    }

    /**
     * Is the user inside their quiet-hours window right now? Window is
     * {start, end} HH:MM in the user's timezone (cross-midnight
     * supported). Misconfigured values never suppress.
     *
     * @param  array<string, mixed>  $settings  resolved CustomerSetting values (key => value)
     */
    public static function quietHoursActive(array $settings, ?CarbonImmutable $now = null): bool
    {
        $window = $settings['quiet_hours'] ?? null;

        if (! is_array($window)) {
            return false;
        }

        $start = self::parseHhMm($window['start'] ?? null);
        $end = self::parseHhMm($window['end'] ?? null);

        if ($start === null || $end === null || $start === $end) {
            return false;
        }

        $timezone = $settings['timezone'] ?? null;

        try {
            $zone = new \DateTimeZone(is_string($timezone) && $timezone !== '' ? $timezone : 'Asia/Kolkata');
        } catch (\Throwable) {
            return false;
        }

        $local = ($now ?? CarbonImmutable::now('UTC'))->setTimezone($zone);
        $minutes = $local->hour * 60 + $local->minute;

        return $start < $end
            ? $minutes >= $start && $minutes < $end
            : ($minutes >= $start || $minutes < $end);
    }

    private static function parseHhMm(mixed $value): ?int
    {
        if (! is_string($value) || preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value) !== 1) {
            return null;
        }

        [$hours, $minutes] = explode(':', $value);

        return (int) $hours * 60 + (int) $minutes;
    }
}
