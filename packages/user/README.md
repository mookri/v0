# User Capability

Purpose: profile, preferences, account state. Identity (auth/sessions) is separate.

Owner: user domain

Entities: User, UserProfile, UserRole
Use cases: get-user, update-profile, deactivate-user, complete-profile
Public API: `@navdhya/user` (domain, contracts, ports, application only — no deep imports)

Events: none owned (consumes identity lifecycle externally)

Dependencies: foundation only
Persistence ownership (Owns): users, profiles
Does not own: authentication, sessions, credentials, pandit profile, bookings
Extraction readiness: ports defined (UserRepository); no Next.js/React/db in domain.
