<?php

namespace Tests\Modules\Booking;

use App\Modules\Booking\Domain\BookingLifecycle;
use PHPUnit\Framework\TestCase;

/**
 * Booking lifecycle invariants (B5, ADR-005): transition legality, guards,
 * terminal states, live actions, and total section coverage.
 */
final class BookingLifecycleTest extends TestCase
{
    public function test_transition_matrix(): void
    {
        $this->assertTrue(BookingLifecycle::canTransition('DRAFT', 'PENDING_PAYMENT'));
        $this->assertTrue(BookingLifecycle::canTransition('PAYMENT_CONFIRMED', 'CONFIRMED'));
        $this->assertTrue(BookingLifecycle::canTransition('IN_PROGRESS', 'COMPLETED'));
        $this->assertTrue(BookingLifecycle::canTransition('ASSIGNED', 'CANCELLED'));

        $this->assertFalse(BookingLifecycle::canTransition('DRAFT', 'COMPLETED'));
        $this->assertFalse(BookingLifecycle::canTransition('COMPLETED', 'CANCELLED'));
        $this->assertFalse(BookingLifecycle::canTransition('CANCELLED', 'DRAFT'));
        $this->assertFalse(BookingLifecycle::canTransition('NOPE', 'DRAFT'));
    }

    public function test_guards_and_terminals(): void
    {
        foreach (['DRAFT', 'PENDING_PAYMENT', 'CONFIRMED', 'ASSIGNED', 'IN_PROGRESS'] as $status) {
            $this->assertTrue(BookingLifecycle::canCancel($status), $status);
        }
        $this->assertFalse(BookingLifecycle::canCancel('COMPLETED'));
        $this->assertFalse(BookingLifecycle::canCancel('PAYMENT_CONFIRMED'));

        $this->assertTrue(BookingLifecycle::canReschedule('CONFIRMED'));
        $this->assertTrue(BookingLifecycle::canReschedule('ASSIGNED'));
        $this->assertFalse(BookingLifecycle::canReschedule('IN_PROGRESS'));

        foreach (['COMPLETED', 'CANCELLED', 'FAILED', 'EXPIRED', 'REFUNDED'] as $status) {
            $this->assertTrue(BookingLifecycle::isTerminal($status), $status);
        }
        $this->assertFalse(BookingLifecycle::isTerminal('CONFIRMED'));
    }

    public function test_actions_only_lists_live_endpoints(): void
    {
        $this->assertSame(['cancel', 'reschedule'], BookingLifecycle::actions('CONFIRMED'));
        $this->assertSame(['cancel'], BookingLifecycle::actions('IN_PROGRESS'));
        $this->assertSame([], BookingLifecycle::actions('COMPLETED'));
        $this->assertSame([], BookingLifecycle::actions('CANCELLED'));
    }

    public function test_sections_cover_every_status_exactly_once(): void
    {
        $covered = [];
        foreach (BookingLifecycle::SECTIONS as $section => $statuses) {
            $this->assertContains($section, BookingLifecycle::SECTIONS_LIST);
            array_push($covered, ...$statuses);
        }

        $all = [];
        foreach (BookingLifecycle::TRANSITIONS as $from => $tos) {
            $all[] = $from;
            array_push($all, ...$tos);
        }

        sort($covered);
        $expected = array_values(array_unique($all));
        sort($expected);
        $this->assertSame($expected, $covered);
    }
}
