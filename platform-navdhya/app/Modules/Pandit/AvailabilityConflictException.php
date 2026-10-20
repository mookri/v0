<?php

namespace App\Modules\Pandit;

/**
 * Two availability slots in the same replace overlap — rendered as 409
 * PANDIT_AVAILABILITY_CONFLICT.
 */
final class AvailabilityConflictException extends \Exception
{
}
