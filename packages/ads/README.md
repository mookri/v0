# Ads Capability

Independent advertising boundary. Ads decide what may be shown; UI decides rendering. Failures degrade to empty — never break booking/payment/pricing.

Owner: ads domain

Entities: Campaign, Creative, Placement, FrequencyStore
Use cases: decide-ad, record-impression, record-click
Public API: `@navdhya/ads` (AdRequest, AdDecision{filled|empty}, renderer name — never React components)

Events: impression, viewable_impression, click, dismiss, conversion (idempotent by eventId)
Dependencies: foundation only — never booking/payment/pricing/pandit internals
Persistence ownership (Owns): campaigns, campaign_flights, campaign_targeting, creatives, creative_versions, placements, placement_formats, delivery_counters, frequency_state, ad_events
Does not own: bookings, payments, puja catalog, user identity
Extraction readiness: CandidateSource/FrequencyStore/TrackingRepository ports; Redis + Postgres behind adapters.
