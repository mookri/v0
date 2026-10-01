<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Modules\Customers\Models\CustomerNotification;
use App\Platform\Tenancy\TenantContext;

/**
 * NotifiesCustomers port implementation: persists the row under the
 * ambient tenant context (producers run tenant-scoped). The explicit
 * tenant id is verified against the ambient context — fail loud on
 * mismatch, never silently cross-tenant.
 */
final class CustomerNotifier implements NotifiesCustomers
{
    public function notify(string $tenantId, string $userId, string $kind, string $title, ?string $body = null): string
    {
        $ambient = app(TenantContext::class)->tenantId();

        if ($ambient !== $tenantId) {
            throw new \RuntimeException('Notifier tenant mismatch: ambient context differs from the target tenant.');
        }

        $notification = new CustomerNotification();
        $notification->forceFill([
            'user_id' => $userId,
            'kind' => $kind,
            'title' => $title,
            'body' => $body,
        ]);
        $notification->save();

        return (string) $notification->getKey();
    }
}
