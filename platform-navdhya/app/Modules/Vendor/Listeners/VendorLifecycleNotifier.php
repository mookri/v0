<?php

namespace App\Modules\Vendor\Listeners;

use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Platform\Outbox\OutboxEvent;
use Illuminate\Support\Facades\Log;

/**
 * Vendor lifecycle listener (D0): consumes the Vendor module's outbox events
 * and sends customer/vendor notifications through the NotifiesCustomers port
 * when a user_id is provided.
 */
final class VendorLifecycleNotifier
{
    /** @var list<string> The lifecycle event types this listener claims. */
    public const EVENT_TYPES = [
        'vendor.profile.created',
        'vendor.profile.updated',
        'vendor.profile.verified',
        'vendor.profile.suspended',
        'vendor.inventory.adjusted',
        'vendor.order.placed',
        'vendor.order.advanced',
        'vendor.order.cancelled',
        'vendor.payout.recorded',
    ];

    public function __construct(
        private readonly NotifiesCustomers $notifier,
    ) {
    }

    public function handle(OutboxEvent $event): void
    {
        $payload = $event->payload;
        $userId = isset($payload['user_id']) && $payload['user_id'] !== '' ? (string) $payload['user_id'] : null;

        if ($userId !== null) {
            $title = match ($event->event_type) {
                'vendor.profile.verified' => 'Vendor Profile Verified',
                'vendor.profile.suspended' => 'Vendor Profile Suspended',
                'vendor.order.placed' => 'New Order Received',
                'vendor.order.advanced' => 'Order Status Updated',
                'vendor.order.cancelled' => 'Order Cancelled',
                'vendor.payout.recorded' => 'Payout Recorded',
                default => 'Vendor Notification',
            };

            $body = match ($event->event_type) {
                'vendor.profile.verified' => 'Your vendor profile has been verified.',
                'vendor.profile.suspended' => 'Your vendor profile has been suspended.',
                'vendor.order.placed' => 'You have received a new order ' . ($payload['order_id'] ?? '') . '.',
                'vendor.order.advanced' => 'Order ' . ($payload['order_id'] ?? '') . ' status changed to ' . ($payload['status'] ?? '') . '.',
                'vendor.order.cancelled' => 'Order ' . ($payload['order_id'] ?? '') . ' has been cancelled.',
                'vendor.payout.recorded' => 'A payout of ' . ($payload['amount_minor'] ?? '') . ' has been recorded.',
                default => 'Vendor event ' . $event->event_type . ' received.',
            };

            $this->notifier->notify(
                (string) $event->tenant_id,
                $userId,
                'vendor',
                $title,
                $body,
                (string) $event->id,
            );
        }

        Log::info("Handled vendor outbox event {$event->event_type}", [
            'tenant_id' => $event->tenant_id,
            'event_id' => $event->id,
            'aggregate_id' => $event->aggregate_id,
        ]);
    }
}
