<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Models\Booking;
use Carbon\CarbonImmutable;

/**
 * Pure read-only preview calculating cancellation refund and fee
 * according to cancellation policy tier without altering any state.
 */
final class PreviewCancellationUseCase
{
    /**
     * @return array{
     *     eligible: bool,
     *     hours_until_start: float,
     *     total_amount_minor: int,
     *     fee_minor: int,
     *     refund_minor: int,
     *     currency: string,
     *     policy_tier: string
     * }
     */
    public function execute(string $tenantId, string $bookingId, int|string $userId): array
    {
        /** @var Booking|null $booking */
        $booking = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        if ($booking === null) {
            throw new BookingNotFoundException('Booking not found.');
        }

        if (in_array($booking->status, [
            BookingLifecycle::STATUS_CANCELLED,
            BookingLifecycle::STATUS_COMPLETED,
            BookingLifecycle::STATUS_FAILED,
            BookingLifecycle::STATUS_EXPIRED,
            BookingLifecycle::STATUS_REFUNDED,
        ], true)) {
            throw new BookingStateForbiddenException("Booking in status '{$booking->status}' cannot be cancelled.");
        }

        $totalMinor = (int) ($booking->amount_minor ?? $booking->total_minor ?? 0);
        $currency = $booking->currency ?? 'INR';

        $hoursUntil = 0.0;
        $feeMinor = 0;
        $refundMinor = $totalMinor;
        $policyTier = 'FULL_REFUND';

        if ($booking->scheduled_at !== null) {
            $start = CarbonImmutable::parse($booking->scheduled_at);
            $now = CarbonImmutable::now('UTC');
            $hoursUntil = round((float) $now->diffInMinutes($start, false) / 60.0, 2);

            if ($hoursUntil < 48) {
                $feeMinor = $totalMinor;
                $refundMinor = 0;
                $policyTier = 'NO_REFUND';
            } elseif ($hoursUntil < 72) {
                $feeMinor = (int) intdiv($totalMinor, 2);
                $refundMinor = $totalMinor - $feeMinor;
                $policyTier = 'PARTIAL_REFUND_50';
            } else {
                $feeMinor = 0;
                $refundMinor = $totalMinor;
                $policyTier = 'FULL_REFUND';
            }
        }

        return [
            'eligible' => true,
            'hours_until_start' => $hoursUntil,
            'total_amount_minor' => $totalMinor,
            'fee_minor' => $feeMinor,
            'refund_minor' => $refundMinor,
            'currency' => $currency,
            'policy_tier' => $policyTier,
        ];
    }
}
