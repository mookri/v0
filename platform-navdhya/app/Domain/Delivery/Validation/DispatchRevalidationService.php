<?php

namespace App\Domain\Delivery\Validation;

use App\Domain\Delivery\State\DeliveryStateMachine;
use App\Modules\Booking\Models\Booking;
use App\Modules\Customers\Models\NotificationDelivery;

/**
 * Revalidation at dispatch (E-CM-03 / §16.2).
 * Verifies that the underlying business aggregate (e.g. Booking) is still active.
 * If cancelled or rescheduled, aborts the send and transitions delivery to CANCELLED.
 */
final class DispatchRevalidationService
{
    public function __construct(
        private readonly DeliveryStateMachine $stateMachine,
    ) {
    }

    /**
     * Revalidates entity state prior to provider dispatch.
     * Returns true if dispatch is permitted; false if cancelled due to state invalidation.
     */
    public function revalidateAndPermit(
        NotificationDelivery $delivery,
        ?string $subjectType = null,
        ?string $subjectId = null
    ): bool {
        $type = strtolower(trim((string) ($subjectType ?? $delivery->kind ?? '')));
        $id = $subjectId ?? $delivery->intent_id;

        if (str_contains($type, 'booking') && $id !== null) {
            $booking = Booking::query()->where('id', $id)->first();
            if ($booking !== null) {
                $invalidStates = ['CANCELLED', 'cancelled', 'EXPIRED', 'expired', 'REJECTED', 'rejected'];
                if (in_array($booking->status, $invalidStates, true)) {
                    $this->stateMachine->apply(
                        delivery: $delivery,
                        targetState: NotificationDelivery::STATUS_CANCELLED,
                        errorCode: 'entity_state_invalidated'
                    );

                    return false;
                }
            }
        }

        return true;
    }
}

