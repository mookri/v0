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
