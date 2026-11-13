<?php

declare(strict_types=1);

namespace App\Modules\Payment\Contracts;

/**
 * Optional compensation hook triggered when uncertain-payment reconciliation
 * discovers a settled charge for a booking whose hold already expired or was cancelled.
 */
interface HandlesUncertainPaymentCompensation
{
    /**
     * Trigger refund/support compensation for a captured payment on an unfulfillable booking.
     *
     * @param  array{tenant_id: string, booking_id: string, intent_id: string, amount_minor: int, currency: string, provider_payment_id: ?string, reason: string}  $context
     */
    public function compensateCapturedUnfulfillableBooking(array $context): void;
}

