# Cutover Log

Append-only evidence for every traffic flip (both directions). No flip
counts without an entry here.

## Format

| Date (UTC) | Step | Direction | Actor | Probes | Result |
|------------|------|-----------|-------|--------|--------|

## Entries

| Date (UTC) | Step | Direction | Actor | Probes | Result |
|------------|------|-----------|-------|--------|--------|
| 2026-10-01 | staging-run | n/a (local) | build-agent | customer :3001 health ok / 401 envelope / home 200; partner :3002 health ok / 401+404 envelopes; web :3000 home+book+user/bookings 200, no redirect (flag off) | PASS — see `docs/operations/staging-deploy.md` |
| 2026-10-01 | strangler-drill | book-class on/off (local) | build-agent | flags off: `/`, `/book`, `/user/bookings`, `/admin/overview` → 200, no redirect. `STRANGLER_CUSTOMER_BOOK=1`: `/book` → 307 `app.navdhya.com/book`, query preserved (`?foo=bar`); `/user/bookings` → 200 (independent class untouched); `/` → 200 | PASS — per-class mechanism proven against production build |
| 2026-10-01 | onboarding-drill | applicant flow (local, `DRILL_OTP_CODE`) | build-agent | start 201 → 7× step PUT 200 → submit/challenge → wrong-code 401 → confirm SUBMITTED → queue lists w/ SLA → approve 200 + `activated.eligible:true` → status APPROVED. Caught missing SUBMITTED→review path; fixed via auto-triage | PASS — full loop proven against production build |
| 2026-10-02 | customer-backend-drill | BFF→Laravel wired reads (local, `CUSTOMER_BACKEND=laravel`, drill tenant, `OTP_LOG_CODES=true`) | build-agent | backend signup 202 → code from drill log → verify 201 + HMAC token; BFF login wrong-proof → 401 mapped; BFF `GET /api/bookings` w/ `__Host-nv_customer_token` → 200 mapped `CONFIRMED` card; BFF detail → 200 actions `[cancel,reschedule,view-puja,contact-support]`; BFF logout → 200 `signedOut` + cleared cookie. BFF login fan-out success covered by stubbed route test (live fan-out is unobservable by design — challenge is single-use). Drill `.env.local` + seed script deleted afterwards | PASS — first wired vertical proven end to end, mocks remain default |
