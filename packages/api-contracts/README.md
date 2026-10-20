# API Contracts

Stable cross-application contracts (BFF + future Global `/v1/` API). Single schema source: TypeScript here → validators, OpenAPI, tests.
Owner: platform (business meaning reviewed by capability owners).
Rules: additive evolution; `data/meta{requestId,pagination}`; error `{code,message,requestId}`; never domain entities or DB rows.
