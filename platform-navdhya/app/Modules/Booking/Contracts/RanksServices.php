<?php

namespace App\Modules\Booking\Contracts;

/**
 * Service ranking port (deptrac-blessed Contracts surface): the discover
 * feed ranks catalog SKUs by booking volume without importing booking
 * models. Ambient tenant context scopes every call.
 */
interface RanksServices
{
    /**
     * @return list<string> SKUs ordered by booking count desc.
     */
    public function popularSkus(int $days, int $limit): array;

    /**
     * @return list<string> distinct SKUs the user booked, most recent first.
     */
    public function customerSkus(string $userId, int $limit): array;
}
