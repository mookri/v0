# Customer BFF

Purpose: Customer-oriented composition boundary (`app.navdhya.com/api/*`). Browser sees use-case endpoints; capabilities own business truth.
Boundary: route handler (parse→validate→actor→use case→map→respond). No DB, SDKs, or domain mutation in handlers.
Endpoints: home, bookings, booking detail, cancel/reschedule (Idempotency-Key), profile, saved, notifications, discover, settings.
Auth: `SignedTokenActorResolver` (identity port) → actor from
`Authorization: Bearer` HMAC tokens; secret is server-only
(`SESSION_SIGNING_KEY`, dev fallback insecure by label). Client userId
headers are never trusted. Same-origin API minimizes CORS.
Errors: `{ error: { code, message, requestId } }` — stable codes, no stack/provider leaks.
Cache: private short-lived reads; none for mutations. Server components call use cases directly (no loopback HTTP).
Extraction: local capability adapters today; remote service clients later without browser changes.
