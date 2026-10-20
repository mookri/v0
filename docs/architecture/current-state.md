# Current Architecture State
## Stack
- Framework: Next.js 16.0.10
- Node Version: 24.14.1
- Package Manager: npm 11.11.0
- Build Tool: Turbopack

## Flows
- **Auth**: Found `app/(auth)` routes (login, signup, reset-password, two-factor) and `lib/auth` (mock-session, contracts).
- **Booking**: `app/book` routes handle scheduling and bookings. `dal/availability.ts` found.
- **Payment**: No native 3rd party (stripe, razorpay) integrated explicitly yet.
- **PWA Behavior**: No standard PWA files (manifest.json, service worker) discovered.

## Features & Routing
- Domain-based routes (astrology, pujas, vastu, pandits)
- SEO generated routes and configuration (e.g., `(seo)`)
- API endpoints exist for ads and other integrations.
