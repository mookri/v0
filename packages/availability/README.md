# Availability Capability

Purpose: platform scheduling truth — slots, capacity, reserve/release. Serves pandit, puja, booking.

Owner: availability domain

Entities: Availability, AvailabilitySlot, SlotId
Use cases: get-available-slots, reserve-slot, release-slot
Public API: `@navdhya/availability`

Events: AvailabilityChanged (planned)
Dependencies: foundation, pandit, puja (by resource ID — no deep import)
Persistence ownership (Owns): slots, schedules, capacity
Does not own: bookings, pandit profile
Extraction readiness: AvailabilityRepository port; resourceId opaque.
