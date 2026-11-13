<?php

namespace App\Domain\Delivery\Support;

/**
 * Event and Template Registry enforcing semantic separation between financial receipt
 * and ceremony booking confirmation (E-CM-04 / Task T-CM04-04).
 */
final class EventTemplateRegistry
{
    public const EVENT_PAYMENT_RECEIVED = 'payment.received';
    public const EVENT_BOOKING_CONFIRMED = 'booking.confirmed';

    public const TEMPLATE_PAYMENT_RECEIPT = 'payment_receipt';
    public const TEMPLATE_BOOKING_CONFIRMATION = 'booking_confirmation';

    /**
     * Asserts that a payment receipt message strictly refrains from claiming
     * that a ritual or booking is confirmed before the booking engine validates pandit assignment.
     */
    public static function validateSemantics(string $eventType, string $templateName, array $variables): bool
    {
        if ($eventType === self::EVENT_PAYMENT_RECEIVED) {
            // Payment receipt variables must focus on transaction details, not pandit/schedule confirmation
            if (isset($variables['pandit_confirmed']) && $variables['pandit_confirmed'] === true) {
                return false;
            }
        }

        return true;
    }
}

