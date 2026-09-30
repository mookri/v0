# Customer feature: bookings

Route: `customer.bookings` (`/bookings`), `customer.booking.detail` (`/bookings/:id`)
Capability: `@navdhya/booking` (status machine, `getBookingActions`); puja/pandit via summaries only.
Application: `application/bookings/{list-customer-bookings,get-customer-booking-details,cancel-customer-booking}.ts`
View models: `CustomerBookingCard`, `CustomerBookingDetails{actions[]}` (derived, never hard-coded).
Analytics: `customer.booking.list.viewed`, `customer.booking.detail.viewed`, `customer.booking.cancel.started|completed`.
Authorization: ownership enforced server-side; cross-actor reads return NOT_FOUND.
Migration state: IN_PROGRESS (Web route remains canonical until rollout gate passes).
