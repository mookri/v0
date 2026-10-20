# Pandit Capability

Purpose: pandit business data — profile, expertise, verification. Role/authorization stays in identity layer.

Owner: pandit domain

Entities: Pandit, PanditProfile, Expertise, VerificationStatus
Use cases: get-pandit, search-pandits, verify-pandit, get-pandit-profile
Public API: `@navdhya/pandit`

Events: PanditVerified (planned)
Dependencies: foundation, user (by ID reference only — no deep import)
Persistence ownership (Owns): pandits, verification, expertise
Does not own: user profile, bookings, availability
Extraction readiness: PanditRepository port; userId kept as opaque string.
