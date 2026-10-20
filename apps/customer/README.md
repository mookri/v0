# Customer App (`app.navdhya.com`)

Authenticated workspace peer of Web — never a copy of `apps/web`.

Owner: customer application
Product: `customer` (feeds DLO-X, analytics, observability)
Consumes: `@navdhya/*` packages only — never `apps/web/*`, never infrastructure drivers directly.
Routes: stable IDs `customer.home|bookings|booking.detail|saved|profile|settings|notifications|support`.
Session: `CustomerSession` interface + adapters (dev stub gated off production; SSO lands in later phase, never tokens in URL).
SEO/Ads: private by default (`noindex`, `customer.ads=false`).

Local: `http://app.navdhya.local:3001`. Staging: `https://app-staging.navdhya.com`.
