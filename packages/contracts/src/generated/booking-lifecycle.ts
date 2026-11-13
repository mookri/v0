// AUTO-GENERATED from platform-navdhya/app/Modules/Booking/Domain/BookingLifecycle.php via `php artisan contracts:export`.
// Do NOT hand-edit. Run `php artisan contracts:export` after editing BookingLifecycle.php.

export const BOOKING_STATUSES = [
  'DRAFT',
  'PENDING_PAYMENT',
  'PAYMENT_CONFIRMED',
  'CONFIRMED',
  'ASSIGNED',
  'IN_PROGRESS',
  'COMPLETED',
  'CANCELLED',
  'FAILED',
  'EXPIRED',
  'REFUNDED',
  'NO_SHOW',
] as const;

export type BookingStatus = (typeof BOOKING_STATUSES)[number];

export const BOOKING_SECTIONS_LIST = ['upcoming', 'active', 'completed', 'cancelled'] as const;

export type BookingSection = (typeof BOOKING_SECTIONS_LIST)[number];

export const BOOKING_LEGAL_TRANSITIONS: Record<BookingStatus, readonly BookingStatus[]> = {
    "DRAFT": [
        "PENDING_PAYMENT",
        "CANCELLED",
        "EXPIRED"
    ],
    "PENDING_PAYMENT": [
        "CONFIRMED",
        "PAYMENT_CONFIRMED",
        "FAILED",
        "EXPIRED",
        "CANCELLED"
    ],
    "PAYMENT_CONFIRMED": [
        "CONFIRMED",
        "REFUNDED",
        "FAILED"
    ],
    "CONFIRMED": [
        "ASSIGNED",
        "CANCELLED",
        "FAILED"
    ],
    "ASSIGNED": [
        "CONFIRMED",
        "IN_PROGRESS",
        "CANCELLED",
        "NO_SHOW",
        "FAILED"
    ],
    "IN_PROGRESS": [
        "COMPLETED",
        "CANCELLED",
        "FAILED"
    ],
    "COMPLETED": [],
    "CANCELLED": [],
    "FAILED": [],
    "EXPIRED": [],
    "REFUNDED": [],
    "NO_SHOW": []
} as const;

export const BOOKING_CANCELLABLE_STATUSES: readonly BookingStatus[] = ['DRAFT', 'PENDING_PAYMENT', 'CONFIRMED', 'ASSIGNED', 'IN_PROGRESS'] as const;

export const BOOKING_RESCHEDULABLE_STATUSES: readonly BookingStatus[] = ['CONFIRMED', 'ASSIGNED'] as const;

export const BOOKING_SECTIONS: Record<BookingSection, readonly BookingStatus[]> = {
    "upcoming": [
        "DRAFT",
        "PENDING_PAYMENT",
        "PAYMENT_CONFIRMED",
        "CONFIRMED"
    ],
    "active": [
        "ASSIGNED",
        "IN_PROGRESS"
    ],
    "completed": [
        "COMPLETED"
    ],
    "cancelled": [
        "CANCELLED",
        "FAILED",
        "EXPIRED",
        "REFUNDED",
        "NO_SHOW"
    ]
} as const;

export const BOOKING_TERMINAL_STATUSES: readonly BookingStatus[] = ['COMPLETED', 'CANCELLED', 'FAILED', 'EXPIRED', 'REFUNDED', 'NO_SHOW'] as const;

export const BOOKING_STATUS_SECTION_MAP: Record<BookingStatus, BookingSection> = {
    "DRAFT": "upcoming",
    "PENDING_PAYMENT": "upcoming",
    "PAYMENT_CONFIRMED": "upcoming",
    "CONFIRMED": "upcoming",
    "ASSIGNED": "active",
    "IN_PROGRESS": "active",
    "COMPLETED": "completed",
    "CANCELLED": "cancelled",
    "FAILED": "cancelled",
    "EXPIRED": "cancelled",
    "REFUNDED": "cancelled",
    "NO_SHOW": "cancelled"
} as const;
