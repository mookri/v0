# Recommendations Capability

Purpose: "what might this user find useful?" — separate from search. Deterministic today, ML-ready later.

Owner: recommendations domain

Entities: Recommendation
Use cases: get-home-recommendations, get-puja-recommendations, get-personalized-recommendations
Public API: `@navdhya/recommendations`

Events: none owned (consumes booking/user signals via RecommendationSource port)
Dependencies: foundation, user, puja, pandit, booking (signals only)
Persistence ownership (Owns): none (no tables owned; stateless rules over signals)
Does not own: search ranking, pricing, bookings
Extraction readiness: RecommendationSource port; deterministic placeholder.
