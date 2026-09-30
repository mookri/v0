# Puja Capability

Purpose: catalog truth — pujas, categories, rituals. UI (PujaCard) stays out until boundary is stable.

Owner: puja domain

Entities: Puja, PujaCategory, PujaType, Ritual
Use cases: get-puja, list-pujas, get-puja-categories, get-puja-details
Public API: `@navdhya/puja`

Events: PujaPublished (planned)
Dependencies: foundation only
Persistence ownership (Owns): pujas, categories, rituals
Does not own: pricing, bookings, availability
Extraction readiness: repository behind PujaRepository port.
