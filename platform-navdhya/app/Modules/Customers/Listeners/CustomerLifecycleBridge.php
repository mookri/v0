<?php

namespace App\Modules\Customers\Listeners;

use App\Platform\Outbox\OutboxEvent;
use Illuminate\Support\Facades\Log;

/**
 * Customer lifecycle listener (E0 / E5):
 * Consumes customer module outbox facts (e.g. spiritual_profile.updated,
 * reminder.fired, consent.changed, account.signed_up) and drives
 * asynchronous customer intelligence, insights refresh, and notification projections.
 */
final class CustomerLifecycleBridge
{
    /** @var list<string> The customer lifecycle event types claimed by this bridge. */
    public const EVENT_TYPES = [
        'customers.account.signed_up',
        'customers.auth.logged_in',
        'customers.profile.updated',
        'customers.account.deleted',
        'customers.spiritual_profile.updated',
        'customers.family_member.updated',
        'customers.reminder.fired',
        'customers.consent.changed',
    ];

    public function handle(OutboxEvent $event): void
    {
        Log::info("CustomerLifecycleBridge handled fact: {$event->event_type}", [
            'tenant_id' => $event->tenant_id,
            'aggregate_id' => $event->aggregate_id,
            'event_type' => $event->event_type,
        ]);
    }
}
