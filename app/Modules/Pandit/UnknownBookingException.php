<?php

namespace App\Modules\Pandit;

/**
 * The booking referenced by an assignment does not resolve inside the
 * ambient tenant — rendered as 422 validation_failed on booking_id.
 */
final class UnknownBookingException extends \Exception
{
}
