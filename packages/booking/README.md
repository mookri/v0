# Booking Capability

Purpose: central transaction — lifecycle, orchestration, events. Consumes user/puja/pandit/availability/pricing via public contracts only.

Owner: booking domain

Entities: Booking, BookingItem, BookingSchedule, BookingCustomer, BookingStatus
Use cases: create-booking, confirm-booking, cancel-booking, reschedule-booking, get-booking, list-user-bookings
Public API: `@navdhya/booking` (createBooking, getBooking, cancelBooking, rescheduleBooking, listUserBookings, booking-rules)

Events (versioned DomainEvent): BookingCreated, BookingConfirmed, BookingCancelled, BookingRescheduled
State machine: DRAFT → PENDING_PAYMENT → PAYMENT_CONFIRMED → CONFIRMED → ASSIGNED → IN_PROGRESS → COMPLETED; terminals CANCELLED/FAILED/EXPIRED/REFUNDED. Enforced in booking-rules.ts — UI renders getBookingActions(), never decides.
Dependencies: foundation, user, puja, pandit, availability, pricing
Persistence ownership (Owns): bookings, booking_items, booking_status_history
Does not own: payment, notifications, user profile, pandit profile, pricing rules, slots
Extraction readiness: BookingRepository + Transaction + EventPublisher ports; no Next.js/React/db in domain.
