<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Modules\Customers\Jobs\DispatchCustomerNotification;
use App\Platform\Tenancy\TenantContext;

/**
 * NotifiesCustomers port implementation (N1 dispatcher entry point):
 * verifies the producer's ambient tenant against the explicit target
 * (fail loud on mismatch, never silently cross-tenant) and enqueues
 * the dispatch job — HTTP requests never run delivery work.
 */
final class CustomerNotifier implements NotifiesCustomers
{
    public function notify(string $tenantId, string $userId, string $kind, string $title, ?string $body = null, ?string $eventId = null): void
    {
        $ambient = app(TenantContext::class)->tenantId();

        if ($ambient !== $tenantId) {
            throw new \RuntimeException('Notifier tenant mismatch: ambient context differs from the target tenant.');
        }

        DispatchCustomerNotification::dispatch($tenantId, $userId, $kind, $title, $body, $eventId);
    }
}
