<?php

namespace App\Modules\Customers\Contracts;

/**
 * Cross-module notification port (deptrac-blessed Contracts surface):
 * producers in other modules (booking transitions first) write customer
 * notifications through here instead of importing customers models.
 */
interface NotifiesCustomers
{
    /**
     * @return string the notification id
     */
    public function notify(string $tenantId, string $userId, string $kind, string $title, ?string $body = null): string;
}
