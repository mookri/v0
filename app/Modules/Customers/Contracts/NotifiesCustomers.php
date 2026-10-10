<?php

namespace App\Modules\Customers\Contracts;

/**
 * Cross-module notification port (deptrac-blessed Contracts surface):
 * producers in other modules (booking transitions first) write customer
 * notifications through here instead of importing customers models.
 *
 * N1: notify() enqueues on the configured queue connection — delivery
 * (routing, dedup, quiet hours) happens on the worker, so there is no
 * notification id to return. Producers that need an identity pass the
 * event identity; the dispatcher dedups per (tenant, rule:event:
 * recipient:channel) so replays collapse instead of doubling inboxes.
 */
interface NotifiesCustomers
{
    /**
     * @param  string|null  $eventId  event identity for dedup (null = one-shot fact, never deduped)
     */
    public function notify(string $tenantId, string $userId, string $kind, string $title, ?string $body = null, ?string $eventId = null): void;
}
