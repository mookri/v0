<?php

namespace App\Modules\Pandit\Contracts;

/**
 * Pandit availability management port (deptrac-blessed Contracts surface).
 * Ambient tenant context scopes every call.
 */
interface ManagesPanditAvailability
{
    /**
     * Replace the whole schedule (delete + insert). The incoming set must
     * contain no overlapping windows on the same day (or override date).
     *
     * @param  list<array{day_of_week: int, start_time: string, end_time: string, slot_duration_minutes: int, is_recurring: bool, specific_date: string|null}>  $slots
     * @throws \App\Modules\Pandit\AvailabilityConflictException when two slots in a group overlap
     *
     * @return list<array{id: string, day_of_week: int, start_time: string, end_time: string, slot_duration_minutes: int, is_recurring: bool, specific_date: string|null}>
     */
    public function replaceAvailability(string $panditId, array $slots): array;

    /**
     * @return list<array{id: string, day_of_week: int, start_time: string, end_time: string, slot_duration_minutes: int, is_recurring: bool, specific_date: string|null}>
     */
    public function getAvailability(string $panditId, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * @return list<array{id: string, day_of_week: int, start_time: string, end_time: string, slot_duration_minutes: int, is_recurring: bool, specific_date: string|null}>
     */
    public function getPublicAvailability(string $panditId, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * Windows applicable to ONE concrete date (B2 instant-book search):
     * recurring rows whose day_of_week matches the date plus one-off rows
     * (is_recurring = false) for that exact date. Empty when the profile
     * is not publicly assignable (VERIFIED + ACTIVE, same gate as
     * getPublicAvailability).
     *
     * @return list<array{id: string, day_of_week: int, start_time: string, end_time: string, slot_duration_minutes: int, is_recurring: bool, specific_date: string|null}>
     */
    public function windowsOnDate(string $panditId, string $date): array;
}
