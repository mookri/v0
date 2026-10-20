<?php

namespace App\Modules\Pandit;

/**
 * The assignment is actionable, but the booking's lifecycle state
 * refuses the fulfillment fact (e.g. accepting an assignment for a
 * booking the customer already cancelled) — rendered as 409
 * PANDIT_BOOKING_WRONG_STATE. The whole verb rolls back: the
 * assignment row keeps its previous status.
 */
final class BookingWrongStateException extends \Exception
{
    public function __construct(
        string $message,
        public readonly string $from = '',
    ) {
        parent::__construct($message);
    }
}
