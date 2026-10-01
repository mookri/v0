# Pandit Onboarding API (`partner.navdhya.com`, track `pandit`)

Base: `/api/v1` versioning applies when the gateway lands; today routes
are unversioned BFF endpoints with stable envelopes. Auth: `Authorization:
Bearer <actor token>` (signed-token resolver); applicants own their
application (`identityId` binding, `403` otherwise).

Track: `pandit-v1` (SLA 48h). Steps: identity, credentials, services,
areas, availability, documents, agreement (+ review, system).

## Endpoints

| Method & path | Body | Success | Errors |
|---|---|---|---|
| `POST /api/onboarding/start` | `{track: 'pandit'}` | `201 {applicationId, track, trackVersion}` | `400` unknown track, `401` |
| `GET /api/onboarding/:id` | — | `200 {application, steps, needs[], evidence}` | `401/403/404` |
| `PUT /api/onboarding/:id/step/:step` | `{value}` (+ `Idempotency-Key`) | `200 {saved: true}` | `400` unknown step, `403`, `409` not editable |
| `POST /api/onboarding/:id/submit` | — | `200 {challengeId}` (OTP sent) | `422` incomplete (`ONBOARDING_INCOMPLETE`), `409`, `429` |
| `POST /api/onboarding/:id/submit/confirm` | `{challengeId, code}` | `200 {status: SUBMITTED}` | `401` bad code, `409` replay, `422` |
| `POST /api/onboarding/:id/documents` | `{kind, contentLength, sha256}` | `201 {documentId, url, expiresAt}` | `400` |
| `GET /api/onboarding/:id/status` | — | `200 {status, rejectionCode, needs[], documents[]}` | `401/403/404` |

## Flow

start → fill steps (debounced PUT, resumable) → submit (OTP challenge,
10-min window, 3 attempts) → confirm → `SUBMITTED` → manual review
(L1 phone + L2 documents + L4 human; 48h SLA) → `APPROVED` → eligible
for assignment. Rejection returns reason-coded `needs[]`; one appeal
with a fresh reviewer.

## Example: submit handshake

```json
// POST /api/onboarding/app_1/submit → 200
{ "data": { "applicationId": "app_1", "challengeId": "ch_7" }, "requestId": "req_x" }
// POST /api/onboarding/app_1/submit/confirm {"challengeId":"ch_7","code":"482910"} → 200
{ "data": { "applicationId": "app_1", "status": "SUBMITTED" }, "requestId": "req_y" }
```

Limits: submit challenges 5/hour/identifier; uploads 10MB max, scoped
15-min URLs; documents address `onboarding/<app>/<kind>/<sha>`.
