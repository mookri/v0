<?php

namespace App\Modules\Booking\Domain;

/**
 * Booking lifecycle (B5, ADR-005): the transaction state machine ported
 * from the frontend booking-rules (single source of truth going forward
 * — the frontend keeps its copy for UI derivation until F2, then the
 * parity doc governs). Nobody outside this module mutates booking state.
 */
final class BookingLifecycle
{
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';

    public const STATUS_PAYMENT_CONFIRMED = 'PAYMENT_CONFIRMED';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_ASSIGNED = 'ASSIGNED';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_REFUNDED = 'REFUNDED';

    public const STATUS_NO_SHOW = 'NO_SHOW';

    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_PENDING_PAYMENT, self::STATUS_CANCELLED, self::STATUS_EXPIRED],
        self::STATUS_PENDING_PAYMENT => [self::STATUS_CONFIRMED, self::STATUS_PAYMENT_CONFIRMED, self::STATUS_FAILED, self::STATUS_EXPIRED, self::STATUS_CANCELLED],
        self::STATUS_PAYMENT_CONFIRMED => [self::STATUS_CONFIRMED, self::STATUS_REFUNDED, self::STATUS_FAILED],
        self::STATUS_CONFIRMED => [self::STATUS_ASSIGNED, self::STATUS_CANCELLED, self::STATUS_FAILED],
        self::STATUS_ASSIGNED => [self::STATUS_CONFIRMED, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_NO_SHOW, self::STATUS_FAILED],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_FAILED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
        self::STATUS_FAILED => [],
        self::STATUS_EXPIRED => [],
        self::STATUS_REFUNDED => [],
        self::STATUS_NO_SHOW => [],
    ];

    /** @var list<string> */
    public const CANCELLABLE = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_CONFIRMED,
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
    ];

    /** @var list<string> */
    public const RESCHEDULABLE = [
        self::STATUS_CONFIRMED,
        self::STATUS_ASSIGNED,
    ];

    /** @var array<string, list<string>> section => statuses */
    public const SECTIONS = [
        'upcoming' => [self::STATUS_DRAFT, self::STATUS_PENDING_PAYMENT, self::STATUS_PAYMENT_CONFIRMED, self::STATUS_CONFIRMED],
        'active' => [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS],
        'completed' => [self::STATUS_COMPLETED],
        'cancelled' => [self::STATUS_CANCELLED, self::STATUS_FAILED, self::STATUS_EXPIRED, self::STATUS_REFUNDED, self::STATUS_NO_SHOW],
    ];

    /** @var list<string> */
    public const SECTIONS_LIST = ['upcoming', 'active', 'completed', 'cancelled'];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function canCancel(string $status): bool
    {
        return in_array($status, self::CANCELLABLE, true);
    }

    public static function canReschedule(string $status): bool
    {
        return in_array($status, self::RESCHEDULABLE, true);
    }

    public static function isTerminal(string $status): bool
    {
        return (self::TRANSITIONS[$status] ?? null) === [];
    }

    /**
     * Customer-facing actions with live endpoints (confirm exists only
     * once payment confirmation lands — never list dead actions).
     *
     * @return list<string>
     */
    public static function actions(string $status): array
    {
        $actions = [];
        if ($status !== self::STATUS_CANCELLED && self::canCancel($status)) {
            $actions[] = 'cancel';
        }
        if (self::canReschedule($status)) {
            $actions[] = 'reschedule';
        }

        return $actions;
    }
}
