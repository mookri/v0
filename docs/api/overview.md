# API Platform

BFF (`app.navdhya.com/api/*`, co-deployed, unversioned) vs Global API (`/v1/`, versioned, curated cross-app contracts).
Conventions: `{data,requestId}` / `{error:{code,message,requestId}}`; status map in `api-contracts`; cursor pagination; additive evolution.
Policy: Idempotency-Key scoped `actor:operation:resource:key` + payload-hash mismatch rejection; retries for 408/429/502-504 only, single-layer ownership; timeouts fast 2s/normal 8s/long 25s.
Auth: session→actor; service identity separate (seam ready, no services deployed). Rate-limit/CSRF/origin via `@navdhya/security`.
Docs: `docs/api/` + OpenAPI generated from TS schema source (not hand-maintained).
