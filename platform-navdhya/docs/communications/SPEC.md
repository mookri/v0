# Communications, Notifications & OTP — Platform Specification v2

**Modules:** new `app/Modules/Communication`, `app/Modules/Notifications`, `app/Modules/Otp`
(slices S125–S144) — extracted from `app/Modules/Customers`, where the working delivery engine
currently lives
**Packages:** `@navdhya/communication`, `@navdhya/notifications`, `@navdhya/otp`
**Status:** Draft — for review. **§1.2 C-01/C-02/C-03 describe a default-on simulated-success path in
a messaging system. That is the highest-severity finding across all four module specs, because the
platform cannot currently distinguish "we notified the devotee" from "we configured nothing."**
**Extends:** ADR-0001 (backend-owned OTP, SUPERSEDED), ADR-0002 (MSG91 widget OTP), ADR-0020–0023
(auth federation/revocation)
**Related:** [`docs/search/SPEC.md`](../search/SPEC.md) §10 (telemetry transport),
[`docs/onboarding/SPEC.md`](../onboarding/SPEC.md) §10 (consent substrate — **shared, not duplicated**),
[`AGENTS.md`](../../../AGENTS.md) (locked brand palette)
**Methodology:** every task in §21 follows [`docs/specs/README.md`](../../docs/specs/README.md) §2
template, §3 AC shapes, §4 Definition of Done.

---

## 0. How to read this document

```text
Epic       E-CM-<nn>           e.g. E-CM-02
Task       T-CM<epic>-<nn>     e.g. T-CM02-03
AC         AC-T-CM02-03-<n>
Defect     C-<nn>              verified defect in shipped delivery code (§1.2)
Intent     notification intent  = one business moment × one recipient, pre-channel (§4)
Delivery   delivery attempt     = one provider call on one channel (§6)
Receipt    provider event       = an authenticated callback about a delivery (§8)
```

Normative words: **MUST** = blocker, **SHOULD** = justified deviation in the PR, **MAY** = optional.
§1 is facts verified against this working tree on 2026-11-10 — every `C-` row was re-read directly,
and the contrast ratios in §12 were computed rather than copied. Claims I could not verify are
marked **(unverified)** and are not used as requirements.

The domain deserves a note on stakes. This is the channel that tells a family their pandit is
coming, that a puja for a deceased relative is confirmed, that a live stream is starting now, and
that an invoice exists. A wrong or missing message here is not a metrics dip — a missed arrival
notification is a ceremony that starts with an empty room. The design bias throughout is therefore
**toward truthfulness over throughput**: a message that reports failure honestly is worth more than
one that reports success falsely, which is precisely the axis C-01 breaks.

---

## 1. Evidence baseline

### 1.1 What actually ships

This is not a greenfield module. A real, tested, event-driven notification core exists — in the
**wrong module**.

| Concern | Shipped reality | Verified at |
| --- | --- | --- |
| Intent → dispatch | `CustomerNotifier::notify()` enqueues a job | `Customers/CustomerNotifier.php:25` |
| Business triggers | Booking lifecycle only: created / cancelled / rescheduled / CONFIRMED / ASSIGNED / COMPLETED / FAILED / REFUNDED copy | `Booking/Application/BookingLifecycleNotifier.php:36-55` |
| Vendor triggers | vendor lifecycle events | `Vendor/…/VendorLifecycleNotifier.php:17-60` |
| Wiring | both registered as **outbox handlers** | `Booking/ModuleServiceProvider.php:42`, `Vendor/ModuleServiceProvider.php:35` |
| Delivery job | `tries=3`, backoff `min(30000, 500·2^attempt)`, per-channel routing matrix, quiet hours | `Customers/Jobs/DispatchCustomerNotification.php:47-62,95-112` |
| Dedup | unique `(tenant_id, dedup_key)` for in-app | `DispatchCustomerNotification.php:118-138`, migration `…000002…:33` |
| Email idempotency | **claim row** `pending → sent/suppressed` | `DispatchCustomerNotification.php:155-211` |
| Email send | Laravel `Mail` + one Blade template, escaped | `DispatchCustomerNotification.php:246`, `resources/views/mail/notification-email.blade.php` |
| In-app | `customer_notifications` + list/read/read-all/unread API | `CustomerNotificationController.php:19,40,70,108` |
| Provider adapters | `Msg91SmsProvider`, `WhatsAppCloudProvider`, `SmtpEmailProvider`, `FakeNotificationProvider` behind one `NotificationProvider` contract | `Customers/Providers/` (8 files) |
| Webhooks | `POST webhooks/msg91`, `webhooks/whatsapp`, throttled 60/min, tenant- and auth-exempt | `Customers/Routes/api.php:170-178` |
| HMAC checks | implemented for both providers | `Msg91SmsProvider.php:66-87`, `WhatsAppCloudProvider.php:88-100` |
| Consent substrate | `consent_ledger` (purposes TRANSACTIONAL / RITUAL_REMINDERS / ANALYTICS / MARKETING), `notification_preferences`, `message_templates`, append-only with hashed IP/UA and `policy_version` | migration `2026_11_10_000004…:17-58`, `CustomerSpiritualController.php:432-440` |
| **OTP** | **mature.** Widget flow, row-lock serialisation, resend 60/180/540 s max 3, TTL 1200 s, 3 attempts, fresh-challenge backoff 600 s + 3/hour, supersession, single-use consumption, codes SHA-256, fail-closed on provider 5xx/429, dev magic token gated to local/testing | `Platform/Identity/Otp/OtpChallengeManager.php:35,68,75-86,96,115,129,148-201,260-273`, `config/otp-policy.php:61-64`, `DbOtpChallengeStore.php:51-60`, `Msg91Client.php:22-84` |
| OTP frontend | real MSG91 widget loader, no `NEXT_PUBLIC_` secrets, bootstrap served server-side | `packages/otp/src/widget/loader.ts:32-38,102-120`, `CustomerAuthController.php:197`, `apps/web/components/auth/OtpScreen.tsx:88` |
| Tests | ~31 relevant, incl. genuine dedup, replay-collapse, tenant-mismatch, suppression and quiet-hours assertions | `tests/Modules/Customers/NotificationDispatchTest.php:100,130,178,236`, `NotificationEmailTest.php:136,150,184` |

**Two things are notably good and should be preserved, not rewritten.** The OTP subsystem is the
most security competently built part of this codebase — fail-closed on provider errors, hashed
codes, serialised attempts, no plaintext logging (grep confirms none; `Msg91Client.php:59-61`
explicitly avoids echoing tokens). And the email **claim-row idempotency** pattern
(`:155-211`) is a correct solution to the exact at-least-once problem §5 of this spec has to solve
for every other channel. The design below extends that pattern rather than inventing a new one.

The three TS packages, by contrast, are mostly thin: `packages/communication` (158 LOC, pure policy
helpers, **zero app importers**) and `packages/notifications` (148 LOC, live-bus only, unrelated to
delivery) are effectively stubs; `packages/otp` (943 LOC) is real and used.

### 1.2 Defect register

Every row below was re-read directly in this tree.

**C-01 — Unconfigured credentials report success. By default, platform-wide.**
Two compounding mechanisms:
1. `config/notifications.php:16` — `'driver' => env('NOTIFICATIONS_DRIVER', 'fake')`. **The default
   is fake.** A production deploy that forgets the env var gets the fake driver.
2. Both real providers fabricate success when secrets are absent:
   `Msg91SmsProvider.php:35-38` — `if (empty($this->authKey)) return ProviderSendResult::success('msg91_sim_'.Str::random(12), ['mode'=>'simulated'])`, and
   `WhatsAppCloudProvider.php:35-37` — the same with `wa_sim_' . Str::random(16)`.

So the failure mode of a misconfigured production system is: every message "succeeds", every
delivery row is green, dashboards show ~100 % delivery, and **no devotee receives anything**. This is
strictly worse than a crash, because a crash is noticed. The comment at
`config/notifications.php:11-13` shows the intent was local-dev convenience — but the polarity is
inverted for a channel that families rely on at a ceremony. **The rule §7.1 establishes: an
unconfigured provider is a loud, immediate, non-retryable configuration error, never a success.**
The `['mode'=>'simulated']` array is also the only place that truth is recorded, and nothing reads it.

**C-02 — The delivery-receipt loop can never close, because nothing writes the join key.**
`notification_deliveries.provider_message_id` is created nullable
(migration `…000002…:28`) and appears in `$fillable` (`NotificationDelivery.php:44`) — and **no
dispatch path ever assigns it.** A repo-wide grep for the column finds exactly three hits: the
migration, the model docblock, and the webhook's `where()` clause
(`CustomerWebhookController.php:68`). Neither `deliverInApp()` nor `deliverEmail()` records a
provider id, and the E4 providers that would have one are never called (C-03). Consequence: webhook
receipts arrive, are (optionally) verified, `update()` runs, **matches zero rows, and returns HTTP
`acknowledged`**. `delivered` / `read` never exist in the system, silently, forever.
Note also `Msg91SmsProvider.php:56` synthesises
`$body['message'] ?? $body['request_id'] ?? Str::random(16)` — so even if wired, the "id" is
sometimes random junk, and `update()` on a NULL-`where` never matches.

**C-03 — The entire provider abstraction is orphaned; email bypasses it.**
`NotificationProvider::send()` has **no production caller**. Grep for `->send(` across
`app/Modules/Customers` + `app/Platform` yields: `DispatchCustomerNotification.php:246`
(`Mail::to($email)->send(new NotificationEmail(...))` — **direct Laravel mailer**),
`SmtpEmailProvider.php:27` (the adapter calling the same thing, itself uncalled), and
`OtpChallengeManager.php:96` (a *different* contract, `PhoneVerificationProvider`). So
`Msg91SmsProvider` and `WhatsAppCloudProvider` are reachable only from tests, the bound singleton
(`Customers/ModuleServiceProvider.php:37-46`) is unused by the dispatcher, and the routing matrix
selects channels nothing can execute. This is the direct cause of C-04.

**C-04 — SMS is routed, selected, and never sent — and the code says so.**
`DispatchCustomerNotification.php:99-105`, verbatim: *"Sms stays routed-but-undelivered until its
provider track."* `NotificationChannels::CHANNEL_SMS` participates in routing
(`NotificationChannels.php:85-87`) and in the fallback matrix, and `deliverSms()` does not exist.
Meanwhile `packages/communication/src/orchestration/dispatch.ts:11-30` implements
`selectChannel`/`fallbackChannel` policy in TS that **no app imports** — so there is a second,
unwired routing model in a second language, which is the Search-spec D-13 / Calendar-spec P-09
duplication pattern arriving a third time.

**C-05 — Consent is computed correctly and consulted nowhere.**
`NotificationConsentGateService.php:24-66` implements a proper purpose × channel matrix. Its only
reference outside its own file is **one test** (`NotificationProviderGatewayTest.php:43-93`). The
real dispatcher decides from `CustomerSetting` EAV keys only
(`DispatchCustomerNotification.php:260-268`, merging stored values over `CustomerSetting::KEYS`) —
never `consent_ledger`, never `notification_preferences`. So the marketing/transactional distinction
that WhatsApp policy, TRAI DLT and DPDP all care about is **enforced by a service that the send path
does not call, against a store the send path does not read.** Worse, there are now **three**
preference systems with different semantics — `CustomerSetting` (authoritative in practice),
`notification_preferences` (populated, unread), and `PreferencesReader.php:37` (`notify_{channel}`
keys) — which guarantees the day someone "fixes" one of them, consent changes behaviour in an
unrelated surface.

**C-06 — Webhook receipt processing writes cross-tenant, unguarded, invalid states.**
`CustomerWebhookController.php:64-73`:
```php
NotificationDelivery::query()->withoutGlobalScopes()
    ->where('provider_message_id', $providerMessageId)
    ->update(['status' => $status, 'updated_at' => now()]);
```
Three separate problems in six lines:
1. `withoutGlobalScopes()` and **no `tenant_id` predicate**, on a table whose own unique key is
   `(tenant_id, dedup_key)` (`…000002…:33`). One tenant's provider callback can update another
   tenant's delivery row if a message id ever collides — and C-01/C-02 show ids are sometimes
   `Str::random()` or junk. **This is a G-TENANT-class violation** (cf. Search spec §10.2:
   tenant predicates belong in retrieval, not after it).
2. The written value is a **raw provider string**, while the model defines exactly three states:
   `pending`, `sent`, `suppressed` (`NotificationDelivery.php:34-38`). `delivered`/`read`/`failed`
   are not valid, and nothing maps them — the column is `varchar(16)` with no enum, no check, and no
   transition guard, so state can regress (`sent → pending`) or be invented.
3. It is reached from a route that is auth- and tenant-exempt (`Routes/api.php:170-178`), so
   (subject to C-07) **any unauthenticated caller can rewrite delivery status**, which is the
   "delivery-state forgery" row in the brief's own threat table, live.

**C-07 — Both webhook signature checks are fail-open.**
`Msg91SmsProvider.php:68`: `if ($this->webhookSecret !== null && $this->webhookSecret !== '')` —
signature verification is skipped entirely when the secret is unset. `WhatsAppCloudProvider.php:93`:
`if ($this->appSecret !== '')` — identical. `MSG91_WEBHOOK_SECRET`
(`config/notifications.php:24`) and the WhatsApp app secret are therefore **optional security
controls**. Combined with C-06.3, the practical result of forgetting one env var is an unauthenticated
state-mutating endpoint. A test currently *asserts* this behaviour
(`NotificationProviderGatewayTest.php:97`, "unsigned webhook accept") — that test is a specification
of a defect and must be inverted, not preserved.

**C-08 — Provider calls have no timeout, no idempotency key, and no ambiguity handling.**
`Msg91SmsProvider.php:43-52` and `WhatsAppCloudProvider.php:48-63` both issue
`Http::…->post()` with **no `->timeout()`**. Contrast `Msg91Client.php` (OTP) which sets 5 s at
`config/services.php:64` and fails closed on 5xx/429 — so the OTP path is hardened and the
messaging path is not, in the same subsystem. Neither provider sends an idempotency key, and
`notification_deliveries` has no `idempotency_key` column (`:20-30`) to store one.
Consequence, exactly the brief's warning: a timeout *after* the provider accepted the request yields
an ambiguous result, the job retries (`tries=3`, `:47-62`), and the recipient gets a **duplicate
WhatsApp confirmation**. Duplicate-send protection exists for in-app (dedup unique) and email (claim
row) and **for neither SMS nor WhatsApp** — confirmed by the test suite: dedup is asserted at
`NotificationDispatchTest.php:100` and replay at `NotificationEmailTest.php:136`, with no SMS/WhatsApp
analogue.

**C-09 — The delivery table cannot express the state machine it is supposed to implement.**
Full column list, migration `…000002…:20-30`: `id, tenant_id, user_id, kind, category, channel,
dedup_key, status varchar(16), provider_message_id, error, timestamps`. **No
`submitted_at`/`sent_at`/`delivered_at`/`read_at`/`failed_at`, no `attempt` count, no
`template_version_id`, no `cost`, no `recipient_reference`, no `notification_intent_id`.** The brief's
§13.1 lifecycle is therefore not representable today. There is a concrete and amusing blocker: the
brief's own state name `PROVIDER_ACCEPTED` is 17 characters and **does not fit in
`varchar(16)`**; `DELIVERY_UNKNOWN` fits only exactly. Any implementation of §6 will silently truncate
unless the column is widened as part of the same migration.

**C-10 — `unique(tenant_id, dedup_key)` silently no-ops on NULL.**
`dedup_key` is `nullable` (`:24`) and MySQL permits unlimited NULLs in a unique index. Any insert
path that leaves `dedup_key` unset is therefore **unprotected by the very constraint that is supposed
to provide idempotency**, and no test covers it. The in-app path does set it
(`DispatchCustomerNotification.php:118-138`); the general contract does not require it.

**C-11 — DLT template mapping is partial and falls through to an unregistered id.**
`config/notifications.php:25-28` defines DLT env vars for **two** templates
(`booking_confirmed`, `ritual_reminder`). `Msg91SmsProvider.php:40` resolves
`config("notifications.msg91.dlt_templates.{$message->template}", $message->template)` — the fallback
passes the *logical* template code as the DLT template id. TRAI's framework rejects content whose PE
id / header / template id do not align with the registered record, so the failure appears at the
carrier as a rejected message, while our own log says "accepted" (C-01 makes this unreadable). The
brief's `sms_templates` registry (§9.5) does not exist; `message_templates` **is** created
(migration `2026_11_10_000004…:17-58`) and has a model, but no dispatch consumer was found — it is a
table with a migration and nothing else.

**C-12 — Transactional email renders neither the brand nor the accessibility guarantees intended.**
`resources/views/mail/notification-email.blade.php:8` uses `color: #1f2937` and `:14` uses
`#6b7280` — stock greys, not the locked palette in `AGENTS.md` (`#FFF7ED` Warm Ivory, `#2C2033`
Aubergine Ink, `#F16A24` Navdhya Flame). No plain-text alternative part (`Mail/NotificationEmail.php:47-57`
registers a single HTML view), no preheader, no responsive/inline-CSS structure, no locale-aware date
or currency formatting, no attachment handling.
I computed the contrast figures the brief cites rather than trusting them, and the brief is **right**:

```text
Aubergine #2C2033 on Ivory #FFF7ED   14.53:1   ✓ AAA
Aubergine #2C2033 on Flame #F16A24    5.01:1   ✓ AA normal, AA large
Flame   #F16A24 on Ivory #FFF7ED      2.90:1   ✗ fails AA for normal text
```
Two honest refinements to the brief's framing. First, the shipped greys are **not** an accessibility
violation — `#1f2937` on Ivory computes to 13.83:1 and `#6b7280` to 4.55:1 (passes AA, but with only
0.05 of margin, so it is one lightening tweak away from failing). So C-12 is a **brand-compliance**
defect today. Second, that matters because the *obvious* fix — "make it on-brand by using Flame" —
creates the actual 2.90:1 violation. §12 therefore specifies token usage **and** the permitted
foreground/background pairings, with a computed check in CI, so the brand migration cannot introduce
the a11y regression it is mistaken for.

**C-13 — No invoice, receipt or financial notification path exists.**
No PDF generator is installed (no `dompdf`/`barryvdh`/`snappy` in `composer.json`), no
`invoice.issued` trigger, and grep confirms the **Payment module contains zero messaging
references**. So the brief's highest-value transactional emails — `payment_receipt_email`,
`tax_invoice_email` — have no producer, no renderer, and no attachment pipeline. This also defers the
brief's most important accounting question (§13) which is legal, not technical: *who is the supplier*
(pandit / institution / platform), and therefore whose GSTIN must appear on the invoice. Navdhya must
not issue an invoice for another entity's supply.

**C-14 — Most of the signature journeys have no event source at all.**
Absent triggers, verified by grep across all modules: **pandit arrival / departure / delay /
no-show**, **live stream session ready/started**, **digital prasad ready**, **physical prasad
dispatch / carrier status**, `payment.received` / `payment.refunded`, booking `EXPIRED` /
`PENDING_PAYMENT`. The brief's §6–§8 flagship features (confirmation cards, arrival alerts, streaming
links, prasad tracking) are therefore **not a messaging project — they are an event-producing
project**. §15 states this plainly, because building WhatsApp templates for events that nothing
emits produces a beautiful UI over silence.

**C-15 — Secret scanning does not cover either provider.**
`.gitleaks.toml` contains **no pattern matching** `msg91`, `whatsapp` or `authkey` (verified: zero
matches), while covering Razorpay/AWS/UIDP. `platform-navdhya/.env` currently holds a non-empty
MSG91/WhatsApp value and is git-ignored (fine), but the scanner that is supposed to catch the next
mistake is blind to exactly the two secrets this platform adds. One-line fix, real risk removed.

**C-16 — No delivery-status surface for staff or recipients.**
Nothing in `apps/*` renders provider status; the only notification UI is an unread-count BFF route
(`apps/customer/app/api/notifications/unread-count/route.ts`) and no status polling exists
(zero `setInterval`/`refetchInterval`). So even once receipts work (C-02), no human sees them, and
§19's operational metrics have no consumer. The brief's dashboard is a genuine gap, not a nicety.

**C-17 — `SmtpEmailProvider` mislabels its own subject line.**
`SmtpEmailProvider.php:17-35` passes `kind: 'notification'`, which resolves to the generic subject
branch at `NotificationEmailTemplates.php:31`. A booking confirmation and a refund acknowledgement
therefore arrive with the same subject. Cosmetic in isolation; a real problem for an inbox that must
let a family find the invoice.

### 1.3 The pattern across all four module specs

Read together with Search D-01/D-11, Calendar P-03/P-08/P-10 and Onboarding T-01/T-05/T-12, a
consistent shape emerges, and it is worth naming because it changes how gates should be written:

| Module | Declared control | Reality |
| --- | --- | --- |
| Search | `W_POP = 0.45` ranking weight | multiplies an all-zero column |
| Calendar | "verified through 2028" | nothing verifies 2026 |
| Onboarding | `second_reviewer`, T3 quorum | client-supplied string; enum with no enforcement |
| Comms | `NotificationProvider` abstraction, consent gate, HMAC, dedup, DLT | **no caller, no caller, fail-open, NULL-permissive, 2 of N templates** |

**Escalation, though:** the first three are *absent enforcement*. This module adds
**fabricated success** (C-01) and a *self-reporting-green* receipt loop (C-02). That is why §22 gates
are written as "the test must fail when enforcement is removed", and why C-01 is the first task in
the entire platform roadmap: **a system that lies about having messaged is not a partially-built
system, it is an un-debuggable one.**

### 1.4 Assumptions to prove, not inherit

| # | Assumption | Proof | Gate |
| --- | --- | --- | --- |
| A-1 | Meta WABA + phone number + system-user token + template approvals are granted in the target markets and languages | configuration evidence + a live test send | **G-PROV-1, blocks E-CM-04** |
| A-2 | MSG91 OTP widget product continues to support the contracted audiences, and **the contracted OTP flow does not permit multi-channel fallback with an application-known code** | provider contract review | G-PROV-2 |
| A-3 | TRAI DLT: Principal Entity, header and per-template IDs are registered for every template the code can select | registry rows with approval state | G-DLT-1 |
| A-4 | A sending domain can be authenticated (SPF/DKIM/DMARC) and separated transactional from marketing | DNS + a seedbox/deliverability check | G-MAIL-1 |
| A-5 | Booking / payment / assignment / arrival / completion / dispatch **emit authoritative domain events** | enumerated event producers per journey | **G-EVT-0, blocks §15 — currently false (C-14)** |
| A-6 | Counsel has determined the invoicing supplier for each supply model | written determination | G-INV-0, blocks §13 |
| A-7 | A courier/carrier event source exists, or dispatch status is entered manually by a partner | operational decision | G-CAR-0 |
| A-8 | The provider APIs' current contracts match the pinned versions (Meta `v21.0`, MSG91 v5) | version-pinned integration test | G-PROV-3 |

A-2 deserves emphasis, because it constrains the brief's §9.2 recommendation. The shipped OTP is
**provider-managed via the MSG91 widget**: `Msg91WidgetProvider.php:28-31` — the backend `send()` is a
*marker*, and verification happens server-side against MSG91's
`/api/v5/widget/verifyAccessToken` (`Msg91Client.php:22-84`). **Navdhya therefore never holds the OTP
code**, which is excellent for security but means the brief's "SMS → voice → WhatsApp → email
failover with one active code" (§9.2/§9.4) is **not implementable in application control** today; the
observed failover is MSG91's own channel rotation (`config/services.php:65`,
`MSG91_RESEND_CHANNELS=11,4`) on the same live challenge (`OtpChallengeManager.php:148-201`), which is
the *right* answer — it satisfies "do not generate a second independently valid code" by construction.
Option B (application-managed) exists in dead code (`OtpChallengeManager.php:115` SHA-256 path).
So §11 keeps provider-managed OTP and does **not** promise cross-provider OTP failover it cannot honour.

---

## 2. Scope and posture

### 2.1 In scope
1. Kill simulated success; make unconfigured mean loud and fail-closed (C-01) — **first task of the whole platform**.
2. Extract a `Notifications` / `Communication` / `Otp` module boundary from `Customers`, keeping working behaviour and tests (§3).
3. A delivery schema that can actually hold the §6 lifecycle, with a provider id written on send (C-02/C-09).
4. Provider layer with timeout, idempotency key, ambiguity reconciliation, per-provider circuit breaker (C-08).
5. Tenant-scoped, signature-enforced, idempotent webhook receipting (C-06/C-07).
6. **One** consent/preference system, enforced at dispatch, migrated from three (C-05).
7. Outbox-backed intent creation with per-channel duplicate suppression (C-10).
8. WhatsApp/Meta integration with template governance, category correctness, opt-in evidence, 24-hour window rules (§10).
9. DLT registry covering every selectable template, with no unregistered fallback (C-11).
10. Transactional email as a brand-token system with computed contrast enforcement (C-12), receipts/invoices (C-13).
11. The five signature journeys, **including the event producers they require** (C-14).
12. Operational truth: receipts, latency, cost, opt-out rates, a real dashboard (C-16).

### 2.2 Out of scope (with owner)

| Excluded | Why | Owner |
| --- | --- | --- |
| Redis / Kafka / RabbitMQ / a push-notification service | DB outbox + Laravel database queue is the established pattern here | none |
| A self-hosted streaming platform | this platform **delivers a link**; it does not host video | separate decision + ADR |
| Courier integration | needs a provider adapter and A-7 | `Fulfillment`/`Finance` + new adapter |
| Marketing campaign tooling | separate opt-in basis, separate reputation domain (§12.4) | later, gated |
| Payment state decisions from messaging | §20 boundary: a user replying "PAID" means nothing | `Payment` module authority |
| WhatsApp Business Catalog / commerce / payments | not required; adds policy surface | none |
| A second OTP implementation | A-2 analysis says provider-managed is correct | none |
| Voice-broadcast / IVR | out of scope until an event source and a use case exist | later |

### 2.3 The external-dependency honesty rule
The brief's "no external infrastructure" principle **cannot hold literally here** and the spec says so
plainly: WhatsApp, SMS and email are necessarily external network dependencies. What is avoidable is
*infrastructure*: brokers, caches, search services. So the boundary is:
**no new self-managed infrastructure; provider APIs only; every provider behind an adapter with a
documented fallback to `manual` or `in_app` when the provider is down** — and a booking MUST NOT
fail because MSG91 is unreachable (§19.4 circuit breaker, and the brief's §22.4 rule "provider
failures do not block booking or payment transactions").

---

## 3. Architecture decision: one kernel, three modules, no business-module calls

**Today:** the engine lives inside `Customers`, so `Booking` reaches notification behaviour by
importing a Customers notifier, and `app/Modules/{Communication,Notifications,Otp}` do not exist.

**Options considered.**
- *Option B — leave it in `Customers` and add providers there.* Cheap, but it entrenches the
  P-09/T-17 mistake: another module wanting to send a message must import a peer's internals, which
  DoD item 6 and `deptrac.yaml` forbid. Every future journey (arrival, prasad, invoice) would then
  add more coupling.
- **Option A (recommended): extract, then extend.**

```text
app/Domain/Delivery/                 kernel: no HTTP, no business rules
  Contracts/    ChannelProvider, DeliveryReceiptVerifier, TemplateRenderer,
                NotificationPolicy, ConsentBasis, EventSource
  Intent/       NotificationIntent, IntentPlanner, DedupKey (required non-null), Suppression
  Lifecycle/    DeliveryStateMachine, DeliveryAttempt, ReconciliationService
  Outbox/       OutboxWriter (fact-of-record), OutboxProcessor, OutboxReconciler
  Registry/     TemplateRegistry, DltTemplateRegistry, ProviderCapabilityRegistry, RateCardStore

app/Modules/Notifications/           policy + scheduling + preferences + intents
app/Modules/Communication/           provider adapters + dispatch + webhooks + cost
app/Modules/Otp/                     the existing Platform/Identity/Otp machine, relocated intact
```

Hard rules, each with a test:
1. **No business controller calls a provider.** Prohibited by Deptrac: `Http/**` cannot reach
   `Domain/Delivery/Providers`. `Booking`/`Payment`/`Pandit` emit **events**; nothing else.
   (Today this happens correctly by accident via `BookingLifecycleNotifier` as an outbox handler —
   preserve and generalise that shape.)
2. **One event → one intent per recipient**, with eligible channel *deliveries* derived from it — not
   three independent systems deciding to message (brief §3.2).
3. Event creation and message dispatch are separate steps joined only by the outbox (§5).
4. The TS packages become **contracts and clients**, never policy owners. The TS
   `selectChannel`/`fallbackChannel` in `packages/communication/src/orchestration/dispatch.ts:11-30`
   is **deleted or made a code-generated mirror** of the PHP routing matrix (D-13's lesson, applied
   before it becomes a fourth instance).
5. `packages/otp` keeps its widget contract; the loader and dev-token gating stay as built — that code
   is good and should be treated as a reference, not a target for rework.

---

## 4. Notification intents and the event catalogue

### 4.1 Intent model
`NotificationIntent` = (business event reference, recipient, subject entity, category, priority,
locale, quiet-hours class, revision binding, dedup key, policy version). It is created **once**,
idempotently, and channel deliveries hang off it. The brief's §15.2 `notification_intent_id` link is
absent today (C-09) and is the missing join for everything in §19.

### 4.2 Event catalogue — with its producer status, which is the point

| Event | Producer today | Blocking work |
| --- | --- | --- |
| `puja.booking.created` | **yes** | — |
| `puja.booking.confirmed` | **yes** | — |
| `puja.rescheduled` / `cancelled` | **yes** | — |
| `puja.completed` / failed | **yes** | — |
| `pandit.assigned` | **yes** (as ASSIGNED copy) | needs the assignment event, not a booking-status proxy |
| `vendor.status.changed` | **yes** | — |
| `booking.reminder_due` | **no** | scheduler (§16.3) |
| `payment.received` / `refunded` | **no** (Payment module has no messaging refs) | A-5 + outbox handler |
| `booking.expired` / `pending_payment` | **no** | A-5 |
| `pandit.departed` / `arriving` / `arrived` / `delayed` / `no_show` | **no** | **field-service event source, §15.2 — largest gap** |
| `stream.session.ready` / `started` / `ended` | **no** | streaming system decision (out of scope §2.2) |
| `prasad.digital_ready` | **no** | media pipeline (§15.4) |
| `prasad.shipped` / `delivery_updated` | **no** | A-7 carrier source |
| `invoice.issued` / `refund.confirmed` | **no** | A-6 legal model + §13 |

Rule, stated as a gate because C-14 makes it necessary: **no template ships for an event that has no
producer.** A template registry entry whose event never fires is how a platform ends up believing it
has coverage it does not have.

### 4.3 Payload discipline
Events carry identifiers, revisions and references — never a customer's birth details, kundli, gotra,
family tree, ancestral dates, Aadhaar/PAN/bank values, or private ritual circumstances (brief §12
data-minimisation list, which is exactly right). The message says "your booking details are in your
Navdhya account" and links. Because `NotificationMessage.variables` is interpolated into provider
templates (§10.4), the variable schema is validated per template and **escaped**, and a variable's
value must be re-derivable from the referenced entity at dispatch time rather than frozen in the
event. This is also what makes §17 revalidation possible.

---

## 5. Outbox, idempotency and honest at-least-once

### 5.1 Why, concretely
The brief's failure examples are real and this codebase already has the parts to fix them:
`BookingLifecycleNotifier` runs as an outbox handler
(`Booking/ModuleServiceProvider.php:42`) — so intent creation is already transactionally sourced for
booking. What is missing is a **provider-send outbox**: `CustomerNotifier::notify()` dispatches the
job directly (`CustomerNotifier.php:25`), and whether that lands inside an open transaction is
**(unverified)** — the agent sweep could not confirm `afterCommit` behaviour for that dispatch. Given
`BookingLifecycleNotifier.php:12-13` runs inside the drain's claim transaction, a rolled-back drain
could leave already-dispatched jobs. **Requirement: dispatch is driven by a committed outbox row, not
by an in-transaction job dispatch, and a test proves a rolled-back transaction sends nothing.**

Note `Platform/Outbox/` (reused by Booking and Vendor) is a *fact* outbox, and
`Catalog/Outbox/CatalogFactAppender.php` is catalog→search feed. Both are the right **pattern** to
extend; neither is a delivery outbox. So §5 reuses an established house mechanism rather than
importing a new one.

### 5.2 Idempotency at four layers, each testable
1. **Event**: unique `event_id` on the outbox row ⇒ a replayed event cannot create a second intent.
2. **Intent**: `dedup_key` **NOT NULL**, composed
   `(tenant, event_type, recipient, subject_entity, revision)` — closing C-10's NULL hole.
3. **Delivery attempt**: `idempotency_key` carried to the provider where the API supports it
   (§7.3), plus the **claim-row pattern already proven at `DispatchCustomerNotification.php:155-211`**
   extended to SMS and WhatsApp.
4. **Receipt**: `(provider, provider_message_id, event_type, occurred_at)` unique on
   `communication_webhook_events`, applied before state mutation — MSG91 documents that retried
   callbacks may arrive multiple times.

### 5.3 Ambiguous result handling
Timeout/5xx after possible acceptance ⇒ state `DELIVERY_UNKNOWN`, **never a blind resend**. A
reconciler (§8.3) queries the provider for the reference, or resolves to `EXPIRED` after a bounded
window, and only then may a fallback channel be considered. The brief's rule
"do not issue another WhatsApp or SMS message just because a webhook is late" becomes an AC, not
guidance.

---

## 6. Channel routing and the delivery state machine

### 6.1 Lifecycle
`CREATED → POLICY_CHECK → (SUPPRESSED | POLICY_BLOCKED) → QUEUED → SUBMITTED →
PROVIDER_ACCEPTED → SENT → (DELIVERED → READ) | FAILED | DELIVERY_UNKNOWN | EXPIRED | CANCELLED`
Provider states map **into** this; nothing outside it is writable (C-06.2). The brief's own
`PROVIDER_ACCEPTED` label overflows today's `varchar(16)` (C-09) — so the column widening is part of
this task, not a later cleanup.

### 6.2 Guards
Legal transitions declared as data (`DeliveryStateMachine::ALLOWED`), enforced in one place, and
**each guard has a test that attempts the illegal edge**. Notably: `DELIVERED` cannot regress to
`SENT`; `CANCELLED` is reachable from anything pre-`SUBMITTED`; `READ` implies `DELIVERED` (a read
without a delivery is a provider inconsistency, logged as such); terminal states are terminal.

### 6.3 Routing matrix (defaults, not fan-out)
The brief's §10.3 matrix is adopted as **config, versioned and inspectable**, with the advanced
decision structure:
```text
if authentication: hand to Otp module (never the notification path)      # brief §9, correct
elif transactional: consent basis + transaction linkage → eligible utility template
elif marketing: explicit marketing opt-in, suppress list, unsubscribe    # cannot fall back
else: service-communication basis + channel policy
```
Per-recipient preference, event importance, category, and **live channel health** (§19.4) all feed
selection; delivery-critical messages never downgrade channel for cost (§18.3).
Routing is evaluated at dispatch, not at event creation (§17.2), so a cancellation, an opt-out or a
template pause in between is honoured.

---

## 7. Provider layer

### 7.1 Fail closed — the C-01 fix, and the platform's first task
```php
if ($this->authKey === '') {
    throw new ProviderNotConfiguredException('msg91.auth_key');   // NOT success
}
```
Rules: a provider with missing credentials **throws a non-retryable configuration error**; the
delivery becomes `FAILED` with reason `provider_unconfigured`; an operational alert fires;
`config/notifications.php` refuses `driver=fake` outside `local`/`testing`/`ci` (hard environment
check at boot, so the unset-env-var path becomes an immediate startup failure rather than a silent
lie); `ProviderSendResult` gains an explicit `simulated` state that **cannot be produced in a
non-dev environment** and is rejected by the state machine; and the `'mode' => 'simulated'` marker is
replaced by that first-class state so a test can assert on it.
AC: GIVEN `MSG91_AUTH_KEY` unset WHEN a booking confirmation is dispatched THEN the delivery is
`FAILED/provider_unconfigured`, an alert event is written, and **no row is marked sent**.

### 7.2 Every provider call has the same four properties
timeout (pinned, matching the OTP path's discipline), connect/read split, retry policy that
distinguishes pre-send (retry) from post-acceptance (reconcile, §5.3), and an idempotency reference.
Per-provider rate limits and concurrency ceilings are config, because MSG91 and Meta each impose
their own.

### 7.3 Capability registry and version pinning
`ProviderCapabilityRegistry` declares per provider × channel: supports templates? media? buttons?
interactive? DLT? signed webhooks? idempotency keys? delivery receipts? category reporting?
`TemplateValidator` refuses to bind a template to a channel whose capability set cannot render it —
which is the structural answer to "template rejected by provider". Meta `v21.0` and MSG91 v5 stay
**explicit pinned config** with a version-pinned integration test (A-8), upgraded deliberately.

### 7.4 Direct-Send caveat
The brief notes Meta's Direct Send capability for certain utility messages (referenced 2026-06-16) —
**(unverified)** from this repository, and it is a policy/cost question as much as a technical one.
Position: **approved templates remain the default**; Direct Send is researched behind a capability
flag and adopted only with verified account eligibility, API-version support and a documented
category/pricing consequence.

### 7.5 Emergency disablement
Per-channel and per-template kill switches, honoured at dispatch evaluation, plus a global
"transactional only" mode. This is the control that makes an ongoing template or policy problem a
button rather than an incident.

---

## 8. Webhooks and reconciliation

### 8.1 Signature enforcement is mandatory, not optional (C-07)
Both providers: **reject when the secret is unset** (fail closed, mirroring
`Msg91Client.php:64-70`'s fail-closed handling on the OTP side). Meta: verify the GET `hub.challenge`
handshake, verify `X-Hub-Signature-256`, and validate that the payload's WABA / phone-number id belong
to *this* tenant's configured identity before doing anything. MSG91: validate the expected
request/message reference and payload shape. The inverted test at
`NotificationProviderGatewayTest.php:97` becomes "unsigned webhook is rejected", and a **forged
signature test** is added to the suite.

### 8.2 Receipt application — tenant-scoped and validated (C-06)
```php
$delivery = Delivery::query()
    ->where('tenant_id', $providerIdentity->tenantId())   // from authenticated provider config
    ->where('provider',  $providerIdentity->provider())
    ->where('provider_message_id', $msgId)
    ->first();                                            // never withoutGlobalScopes()
if ($delivery) { $machine->apply($delivery, $receipt); }  // guarded transition, not raw update()
else { record an unmatched receipt as a metric; }         // never silent
```
No raw string writes: receipts map into §6.1 states through the guard. Unmatched receipts are
counted, which is how C-02 becomes visible instead of invisible.

### 8.3 Reconciliation loop-closing
`delivery:reconcile` walks `SENT`/`DELIVERY_UNKNOWN` rows older than a threshold and asks the
provider (where an API exists) instead of waiting for a webhook. Bounded, batched, resumable.
This is also the mechanism that detects C-02-class regressions in production: the count of
"submitted but never receipted" is the honest delivery rate.

### 8.4 Business-state firewall
A webhook may update **communication delivery records only**. It may never mark a payment received,
a booking confirmed, a puja completed, or an invoice issued — those come from `Payment`/`Booking`
authority. Enforced by a test asserting no business-model write occurs inside webhook handling, not
by a comment. (The brief's "user replies PAID" scenario generalises: inbound WhatsApp content is
**untrusted input**, stored for review, never applied to state.)

---

## 9. WhatsApp Business

### 9.1 Setup is a gate, not a task
A-1 resources — Business Portfolio, WABA, registered number, Phone Number ID, business app, system-user
token, webhook subscription, approved templates — are **launch prerequisites**. The spec cannot
"implement" them, so E-CM-04 is blocked on G-PROV-1 evidence rather than on code. Tokens stay
server-side; the sweep found no `NEXT_PUBLIC_` provider secret, and §20 keeps that property
testable.

### 9.2 Category correctness
Utility for transactional/order updates, Authentication for OTP, Marketing for promotions, Service
for replies inside the 24-hour window — **encoded as a required template attribute**, not inferred at
call time. Consequences of getting it wrong are policy and pricing, both external and both hard to
reverse:
- No promo content in a utility template (the brief's "invoice + festival offer" trap), enforced by a
  template-lint step rejecting marketing tokens in non-marketing categories.
- Business-initiated conversations outside the window require an approved template; free-form is
  permitted only inside the 24-hour service window **after a user message**, and the window state is
  tracked per recipient × thread.
- Automated conversations expose a support-escalation path (Meta requirement) — so every template
  carries a support action, and §10.4 makes that a render-time check.
- Opt-in is recorded with source, purpose, notice version and timestamp (§14) before any proactive
  send; opt-out (block/report, where surfaced) suppresses the channel permanently for that purpose and
  **never auto-substitutes marketing SMS** (brief Scenario G).
Quality rating is monitored per §19; a degrading rating is an operational alert, because a limited
WABA silently stops delivering and — given C-01 — nobody would notice.

---

## 10. Template governance

### 10.1 One registry with lifecycle state
`communication_templates` + `communication_template_versions`, storing the brief's §18 fields —
`template_code, channel, locale, category, provider_template_id, version, approval_status,
variable_schema, example_payload, content_hash, effective_from/until, last_validated_at` — plus
`dlt_registration_ref` for SMS (§11) and `capability_requirements`. `message_templates`
(already created at migration `2026_11_10_000004…:17-58`, currently consumed by nothing) is the
natural table to evolve rather than adding an eighth parallel store (C-05, C-11).

### 10.2 Pause propagation
When Meta or MSG91 rejects or pauses a template, sending for that template **stops automatically** —
it does not degrade into free-form content or an unregistered DLT id (which is exactly C-11's fallthrough).
Structure: approval status is read at dispatch; `approval_status != approved` ⇒
`POLICY_BLOCKED` with reason `template_not_approved`, and the intent becomes eligible for an approved
alternative or falls back to `in_app` + account notification. `last_validated_at` staleness beyond a
threshold triggers re-validation rather than silent use.

### 10.3 Variables, locales, fallback
Per-template JSON Schema for variables (§20 threat: malicious template variable), strict typing,
length bounds and **escaping at render**, with a rejection test for injection-shaped input. Locale
resolution with a documented fallback chain (`hi` → `en`, never a missing translation), and
locale-aware date/currency formatting. `@navdhya/i18n` already models `LOCALES = ['en','hi']` and
`HREFLANG_BY_LOCALE`; templates reuse it rather than adding a third locale notion.

### 10.4 Render-time invariants
Every rendered message must carry: an unsubscribe/support path where required by category, a real
deep link (not a fabricated one), correct entity references resolved at render, and no personal or
devotional detail beyond the purpose's data-minimisation allowance (§4.3). Support/deep links come
from **recorded booking/puja entities** — the brief's "arrival estimate must come from an actual
operational source, not a fabricated static message" is enforced here: an ETA with no recorded source
renders as "no arrival time available", which is a truthfulness requirement, not a UX preference.

---

## 11. SMS and TRAI DLT compliance

### 11.1 DLT is an operational gate with a data home
The brief is right that this is a **mandatory operational dependency**, and today's code has no place
to put it (C-11). `sms_dlt_templates` registry:
`template_code, message_category (OTP|TRANSACTIONAL|SERVICE|COMMERCIAL), pe_id, header,
dlt_template_id, provider_template_id, language, unicode_flag, content_hash, approval_status,
effective_from/until, registered_at, evidence_reference`.
Dispatch requires a registered row **before** attempting a send; absence ⇒ `POLICY_BLOCKED` with
`dlt_not_registered`, never a fallthrough to the logical template code. That single change converts a
silent carrier-level rejection into a visible application state.

### 11.2 Content discipline
Approved template text only — no dynamically invented message bodies outside the approved structure.
Per-purpose templates (OTP, booking confirmation, payment confirmation, pandit assignment, reminder,
prasad dispatch) rather than one generic body. Hindi/Unicode handling follows MSG91's documented
Unicode parameter and is a template attribute, not a call-site flag. Consent-template linkage where
the framework requires it lives in the same registry, and PE/header/template-id are transmitted
together.

### 11.3 Delivery vs acceptance
`Provider accepted ≠ handset delivered ≠ read` is already the §6.1 lifecycle's reason to exist; SMS is
where it bites hardest, since carrier rejection happens after acceptance. Reconciliation (§8.3) plus
`DELIVERY_UNKNOWN` is the honest model. Credits/balance monitoring (§19) is an operational control
with an alert, because an exhausted MSG91 account is otherwise indistinguishable from success —
which, after C-01, is the whole theme of this document.

---

## 12. Transactional email and the Navdhya brand system

### 12.1 Transport stays provider-neutral
`SmtpEmailProvider` exists; keep the interface, allow MSG91 Email or the configured transport as
implementations, and **never couple invoice generation to an email vendor** (brief §24.3).
`Mail::to()->send()` at `DispatchCustomerNotification.php:246` is migrated behind the interface
(C-03) while preserving the claim-row idempotency that makes it correct today.

### 12.2 Brand tokens with computed contrast enforcement
Replace `#1f2937`/`#6b7280` (`notification-email.blade.php:8,14`) with the locked palette as
**tokens** — a single shared token source used by `design-system`, the email Blade templates, and the
docs — and pair it with a CI check on permitted combinations, computed the way §1.2 C-12 computed
them:

| Foreground | Background | Ratio | Use |
| --- | --- | --- | --- |
| `#2C2033` Aubergine | `#FFF7ED` Ivory | **14.53:1** | all body text ✓ AAA |
| `#2C2033` Aubergine | `#F16A24` Flame | **5.01:1** | CTA label on primary button ✓ AA |
| `#F16A24` Flame | `#2C2033` Aubergine | **5.01:1** | accent text on dark panels ✓ AA |
| `#FFF7ED` Ivory | `#2C2033` Aubergine | **14.53:1** | header/footer text ✓ AAA |
| `#F16A24` Flame | `#FFF7ED` Ivory | **2.90:1** | ✗ **prohibited for text** |

Two guardrails that exist because of the C-12 analysis:
1. Flame on Ivory is banned **for text of any size** (2.90 < 4.5). Flame may be used for rules,
   borders, icons, and a button fill carrying Aubergine label text — the brief's recommendation, now
   with the numbers behind it.
2. `AGENTS.md`'s locked distribution keeps Flame ~10–14 % of visible area with Ivory dominant; the
   CI check may assert a rough ratio so a future "brand refresh" cannot flip the email to an
   orange-dominant design that breaks both the palette contract and AA simultaneously.

### 12.3 Email engineering requirements
Responsive HTML with inline CSS (email clients strip `<style>` reliably only in limited clients),
a **`text/plain` alternative part** (absent today — `NotificationEmail.php:47-57` registers one
view), preheader text, 600 px layout, web-safe font stack with a fallback chain, alt text on images,
touch-target sizing, per-client validation matrix (Gmail web/Mobile Gmail/Outlook/Apple Mail —
Outlook's Word rendering engine is a distinct failure surface worth a documented test), no scripts or
iframes, no remote CSS. **No web-font injection for devotional glyphs**: Devanagari in a subject line
or body depends on recipient font support; where the audience is bilingual, prefer Latin transliteration
for the subject with Devanagari in the body, and note that the brand's fold map and the email path
should agree with the same vocabulary (`@navdhya/i18n` + the Search §4 artifact).

### 12.4 Domain separation and deliverability
`transactional.navdhya.com` and `marketing.navdhya.com` as **distinct authenticated sending
streams** (A-4), so a campaign never degrades invoice reputation. SPF, DKIM, DMARC (start at
`p=none`, monitor, escalate deliberately), TLS, PTR where the transport allows,
`List-Unsubscribe` + `List-Unsubscribe-Post` one-click for any subscribed mail, and Gmail/Yahoo bulk
sender requirements met **before** marketing volume exists. `config/mail.php:111-113` still carries
Laravel's stock `hello@example.com` default — a placeholder that must not be what a real receipt is
sent from; §21's task fixes the from-identity as config with validation.
Bounces and complaints feed a suppression registry; a hard bounce suppresses financial-channel email,
which then requires an alternate receipt-delivery path — so §13 and §12.4 are coupled and land
together.

---

## 13. Receipts, tax invoices and the supplier question

### 13.1 The distinction is legal, and engineering cannot choose it
A **payment receipt** acknowledges money received. A **tax invoice** is a statutory document issued by
the *supplier* of a taxable supply, with Rule 46 particulars (supplier legal name, GSTIN, unique
financial-year serial number, date of issue, recipient details where applicable, HSN/SAC, description,
taxable value, tax breakdown, place of supply, signature). **Therefore: Navdhya must not issue a tax
invoice as supplier of a service actually supplied by a pandit or a temple.** Whether Navdhya invoices
its own facilitation/marketplace fee, and whether the pandit or the institution is the service
supplier, is determined by the actual contractual and tax structure — G-INV-0 / A-6, counsel input,
not a config value an engineer picks.

Engineering's obligations, which are unambiguous regardless of how A-6 resolves:
1. The invoice's issuer party is **data derived from the supply model**, not a template constant, and
   the document states which role it is asserting.
2. Financial-year serial numbers are allocated atomically per issuing entity — never generated from a
   timestamp, never reused, with a gap-detectable sequence and an audit trail.
3. Invoice content is generated from an **immutable snapshot** created at issue time. Later changes to
   catalog prices, taxes or entity names must not mutate an issued document; corrections are credit or
   debit notes, never edits. (The `Booking` module's recent duration-snapshot work shows the repo
   already accepts snapshotting as a pattern.)
4. A **rendered invoice is never regenerated from live data on read**; delivery renders the snapshot.
5. Nothing about e-invoicing eligibility or thresholds is hardcoded in a template engine — they come
   from configuration carrying an effective date and a source citation, since they change.
6. Suppression or a bounce on a financial notification triggers an alternative delivery path
   (in-app + secure account download), because §12.4's reputation isolation is not a guarantee.

### 13.2 Absent today, and the order it must be built in
No PDF toolchain (`composer.json`: no dompdf/barryvdh/snappy — C-13), no `invoice.issued` producer,
no snapshot table, no serial allocator, no attachment pipeline. Order: A-6 determination → snapshot +
serial model → generator → renderer → delivery, since the last three are pointless without the first
two.

---

## 14. Consent, preferences and suppression — one system

C-05 found three systems; the fix is convergence, with an explicit deprecation path so `CustomerSetting`
does not linger as a shadow preference store.

### 14.1 Purposes, per channel, per recipient
Purposes the platform actually needs (extending the existing `consent_ledger` enum rather than
inventing a new one): `authentication`, `booking_updates`, `service_updates`, `digital_delivery`,
`financial_communications`, `marketing`, `festival_updates`. Each **× channel**
(whatsapp|sms|email|in_app|push-later) with independent state, plus a basis label
(`consent`, `contract_necessity`, `legitimate_interest`, `law_required`) — because a booking
confirmation and a festival offer are not the same kind of communication and should not share a
consent row.

### 14.2 Emitted controls, versioned
Purpose-specific notice at capture; `notice_version` and `consent_version` stored (the
`policy_version = '2026.1'` precedent already exists at `CustomerSpiritualController.php:432-440`);
withdrawal time and channel recorded; opt-out honoured per channel; a suppression registry with
reason and expiry where temporary; **marketing never auto-substitutes after a WhatsApp block**
(Scenario G), which is an assertion, not a policy statement.

### 14.3 Evaluation at dispatch, in one place
`NotificationPolicy` evaluates: recipient entitlement (may they see this booking at all — a family
member is not automatically entitled to another member's details), basis + consent state per purpose ×
channel, current transaction linkage and status, event currency (the revision the intent was created
for is still the current one), time sensitivity and quiet hours, channel health, and template
capability/approval — **at dispatch** rather than only at event creation, so an opt-out or
cancellation between the two is honoured. This replaces the parallel `CustomerSetting` read at
`DispatchCustomerNotification.php:260-268`, and the AC is written as a falsifiable control: *the
dispatch path's decision changes when the consent ledger changes*, tested directly.

### 14.4 Relationship to the Onboarding privacy work — do not duplicate
`docs/onboarding/SPEC.md` §10 defines a shared `app/Domain/Privacy` consent/retention/erasure
substrate. **This module is a consumer of that kernel, not a second implementation** — the same
rule D-13/P-09/T-17 apply. Practical ordering: the privacy kernel's task list is written once, in
Onboarding E-TR-06; here we require the purposes/withdrawal/erasure hooks it provides. Where timing
forces the notification side to build first, it builds against the `ConsentBasis` interface (§3) and
the storage is explicitly the shared substrate's, so the merge does not fork.

---

## 15. The five signature journeys

Each is a **system, not a template**, and each names the event source it lacks (C-14). Ordered by
product value, not by implementation ease.

### 15.1 Journey A — booking → confirmation
`booking.confirmed` **already fires**. Add: a distinct `PAYMENT_RECEIVED` vs `BOOKING_CONFIRMED` —
a payment must not imply confirmation until booking rules are satisfied (the brief's advanced rule),
so the two are separate events, templates and states. Then a WhatsApp utility card
(image header, dynamic body, booking ref, date/time, pandit where assigned, secure deep-link button,
support action) plus email, SMS only as configured fallback.

### 15.2 Journey B — pandit assignment → arrival *(largest gap)*
States: `PANDIT_ASSIGNED · CONFIRMATION_PENDING · CONFIRMED · DEPARTED · ARRIVING · ARRIVED ·
DELAYED · NO_SHOW`. **Requires an authoritative operational source.** Today none exists for arrival
(C-14). Two sub-decisions to resolve before templates: (1) who signals departure — the pandit via a
partner app action, a dispatch coordinator, or a scheduled rule; (2) what ETA means without location
data, since `packages/pandit` has no live-tracking code. Until both are answered, the honest message
is "your pandit is assigned — contact details in your account", and no ETA.

Arrival-alert controls, as the brief specifies: notification only on an authoritative state change;
materiality threshold (a configurable ~10-minute shift, **tested against user experience rather than
assumed**); only to recipients entitled to see the booking; **the pandit's precise GPS location is not
exposed by default**; any location sharing is separately enabled and time-limited — which is a privacy
control on the *worker*, not just the customer, and worth stating in the assignment terms.

> **Convergence update (S165–S184 / ADR-0071):** Authoritative arrival/start evidence is now produced by the partner courtyard `start` verb (`POST /api/pandit/assignments/{id}/start` → `PanditAssignmentService::start()`), resolving the A-07 / C-14 arrival-timestamp gap for departure/arrival notifications without relying on continuous GPS tracking.

### 15.3 Journey C — live streaming access
The platform **delivers a link**; hosting is out of scope (§2.2). Flow: session created → ownership
verified → **per-recipient** token generated → utility notification → authorized player → access
expires with the session.
Link security, non-negotiable: unique recipient-scoped token, expiry, booking/session binding,
revocation, **no permanent public recording URL**, no birth/family identifiers in URL parameters,
access logging, playback permission validated server-side on each load. Cancellation ⇒ revocation
per booking policy. Failure table as policy: stream not ready ⇒ **no link sent** (never a false live
URL); link expired ⇒ authorized refresh; unauthorized access ⇒ deny + log; multiple family viewers ⇒
seat/access policy. The brief's own "if the streaming session has not started, do not send a
live-now notification" is an AC, because a premature link is a worse experience than a late one.

### 15.4 Journey D — digital prasad
`puja.completed` **already fires**; the artifact pipeline does not exist. Flow: completion → artifact
created (certificate, approved photographs, video, ceremony media) → content scan → ownership check →
private storage → short-lived signed download URL → notification → `delivered/opened/downloaded`.
Rules: **never describe generated digital content as physical sanctified prasad** (product-truth
requirement); **no automatic upload of images of people or sacred ceremonies to a public media
repository**; explicit permission for participant imagery, retained as purpose-specific consent (§14);
SHA-256 integrity; encrypted access tokens; download limits where suitable; a media retention schedule
that is **devotional-sensibility aware** as well as DPDP-aware — a photo of a deceased relative's
śrāddha should not be retained on the same default as a marketing asset, and the retention class should
say so explicitly.

### 15.5 Journey E — physical prasad dispatch
States `PRASAD_PREPARATION → PACKED → HANDOVER_TO_CARRIER → DISPATCHED → IN_TRANSIT →
OUT_FOR_DELIVERY → DELIVERED | FAILED | RETURNED_TO_ORIGIN`, with consignment number, carrier,
dispatch timestamp, tracking reference, recipient acknowledgement. Requires A-7. **A `puja.completed`
event must not mark a shipment dispatched** — two distinct processes, so two distinct event sources,
with a test asserting the independence. Notifications fire only on meaningful state changes, with
dedupe on `(consignment, status)` so a carrier that posts the same scan four times sends one message.

---

## 16. Scheduling, cancellation and revalidation

### 16.1 Reminder schedule, as config
Suggested starting point (the brief's Scenario F): confirmation immediately, 24 h before, 3 h before,
arrival updates event-driven, post-ceremony follow-up after completion — each individually
switchable, **not** a compulsory burst. Every scheduled message binds to a **booking revision**; a
reschedule or cancellation **cancels or recalculates** pending reminders for earlier revisions. Today
no scheduler exists (C-14, `booking.reminder_due` has no producer) and the booking reschedule event
fires but nothing downstream cancels schedules.

### 16.2 Revalidate at dispatch, always
The brief's §22.4 items "scheduled messages revalidated at dispatch" and "no notifications from
uncommitted transactions" are the same control: at send time, re-fetch the referenced entity and
confirm it is still in the state that justifies the message. Cheap, and it kills the brief's most
cited weakness — "a notification can arrive after a booking is cancelled".

### 16.3 Quiet hours, timezone correctness, delivery-window clamping
Quiet hours already exist and are tested (`NotificationDispatchTest.php:236`) — extend them as
per-recipient preference with **IANA timezone resolution from the recipient's own record**, and
clamp: never send a "3 h before" reminder at 02:00 local; move it to the nearest allowed window and
mark the intent `deferred` rather than silently dropping it. Urgent classes (pandit delay affecting a
live ceremony) may override quiet hours, and that override is itself consent-and-category-gated and
logged.

---

## 17. Instrumentation, cost and operations

Reuses the Search spec E-SR-01 transport (`packages/analytics` registry + collector) rather than
adding a second one.

### 17.1 Metrics that answer real questions
| Metric | What it actually tells you |
| --- | --- |
| provider acceptance vs delivery vs read, **per channel** | separates our bugs from carrier behaviour (and would have caught C-02) |
| `DELIVERY_UNKNOWN` rate + unmatched receipt count | whether the loop is closing — the C-02/C-06 health signal |
| duplicate-suppression rate | whether idempotency works |
| OTP success by audience, resend/lockout rates | whether real users can log in |
| consent check hit/miss ratio at dispatch | whether §14 is wired or bypassed |
| template rejection/pause events, DLT rejections | policy/cfg health, immediately actionable |
| queue lag, callback lag, outbox age | pipeline stalling before users feel it |
| cost per message by channel × category × market, from versioned rate cards | economics; nothing hardcoded |
| opt-out, bounce, complaint rates | whether recipients want this |
| **journey completion by touchpoint** (confirmed → arrived → completed → prasad opened) | the actual product signal |

And the anti-metric, worth keeping visible in the dashboard copy: **a read receipt is not proof the
information was understood or the service delivered** (brief §20.1). Read-rate is an operational
signal, never a product success metric.

### 17.2 Dashboards must show measured reality
The brief's illustrative dashboard ("Queued 128 / Pending callbacks 34 / …") is exactly the thing to
be careful about: it is not live data, and today (C-09/C-16) there are no columns or surfaces to make
it live. Requirements: per-provider operational state from **real health checks**, outbox and queue
depth, callback backlog, delivery exceptions, active OTP challenges — all measured, all dated, all
attributed.

### 17.3 Alerting
On provider auth expiry, template pause/rejection, low MSG91 credits, DLT validation failure, OTP
delivery-success drop, email auth failure, bounce/complaint threshold, **outbox processing stopped**,
callback endpoint unavailable, unexplained reminder-queue growth — plus an explicit
**`provider_unconfigured` alert** (C-01's successor) and an **unmatched-receipt spike** alert (C-02's).

### 17.4 Circuit breaker per provider
Opens on error-rate threshold; while open, transactional intents take an alternate eligible channel
or park in a resumable queue, and **booking/payment transactions never block**. On half-open probe
success, drain the parked intents in order.

### 17.5 Cost-aware routing, with a hard floor
Rate cards as versioned data with effective dates (`C_wa = M + P + T` per the brief's model, per-market
and per-category); cost is a routing **input**, never a downgrade trigger for authentication or
financial communication (§20's critical rule).

---

## 18. Security and threat model

Inherits the brief's §21 table; the rows that map to verified current gaps are marked.

| Threat | Required defence | Status |
| --- | --- | --- |
| OTP brute force | per-challenge + per-destination attempt limits | **done** (`OtpChallengeManager.php:75-86`) |
| SMS bombing | rate limits, abuse detection | partial — caps exist; no per-IP/device dimension (§18.1) |
| Credential theft | env isolation, rotation, secret scanning | **gap: `.gitleaks.toml` has no msg91/whatsapp rules (C-15)** |
| WhatsApp webhook forgery | signature verification | **fail-open (C-07)** |
| MSG91 callback replay | idempotency + reconciliation | **absent (C-06)** |
| Duplicate booking confirmations | outbox event keys | partial — in-app/email only (C-08) |
| Malicious template variable | strict schema + escaping | **absent: variables interpolated unvalidated** |
| Email HTML injection | trusted rendering, escaping | partial — `{{ }}` escaping present, no sanitiser path |
| Streaming link leakage | signed, expiring, revocable | **absent (no producer, §15.3)** |
| Prasad media exposure | private storage + authorization | **absent (§15.4)** |
| Invoice modification | immutable financial snapshot | **absent (§13.1)** |
| Notification to wrong family member | recipient entitlement check | **absent (§14.3)** — `family_member` records exist |
| Unauthorized marketing | purpose-specific opt-in | **gate exists, is unwired (C-05)** |
| Delivery-state forgery | provider reference validation | **fail-open + unguarded write (C-06/C-07)** |
| Insider misuse | sensitive-action audit + permissions | partial — audit exists, coverage unmeasured |

### 18.1 Additions specific to this platform
Per-IP and per-device OTP/send limits alongside per-identifier, and a **destination-change bound** so
an attacker cannot walk a phone-number space with one stolen session; CAPTCHA or an added control when
abuse indicators warrant (brief §9.4 list — items 1, 8, 9, 10 are currently unimplemented).
Non-enumerating responses: never reveal whether an account exists.
`in_app` notification content is **personal data in a web surface** — tenant-scoped, owner-scoped,
CSRF-protected, and absent from cacheable responses. `NotificationDelivery` uses
`BelongsToTenant` while the webhook escapes it with `withoutGlobalScopes()` (C-06) — that contrast is
the finding.

### 18.2 The boundary that matters most
**Communications must never become a second source of truth for money.** A WhatsApp payment
confirmation is a notification. A verified payment-provider callback is a payment event. A receipt
email evidences an acknowledgement. The ledger is the record. A user replying "PAID" — or any
inbound content — changes nothing. Enforced by §8.4's test, not by guidance.

---

## 19. Data model summary

Additive; existing tables are evolved in place rather than shadowed.

```text
notification_outbox            event_id UNIQUE · aggregate · revision · status · next_attempt_at
notification_intents           tenant · recipient · subject ref + revision · category · priority
                               · locale · dedup_key NOT NULL · policy_version · created_at
                               UNIQUE (tenant, dedup_key)
communication_deliveries       (EVOLVE — C-09: widen status, add columns)
                               + intent_id · provider · template_version_id · idempotency_key
                               · submitted_at · accepted_at · sent_at · delivered_at · read_at
                               · failed_at · attempt_count · last_error_code · unit_cost
                               · recipient_reference_hash · reconciled_at
                               UNIQUE (provider, provider_message_id, tenant_id)
communication_delivery_attempts delivery_id · attempt_no · started/finished · http_status · outcome
                               · ambiguous_flag
communication_webhook_events   provider · event_type · provider_message_id · occurred_at · payload_hash
                               · processed_at · UNIQUE (provider, provider_message_id, event_type, occurred_at)
communication_templates · communication_template_versions · sms_dlt_templates · provider_rate_cards
notification_preferences (converge — C-05) · notification_suppressions
stream_access_tokens · digital_artifacts · prasad_shipments   (§15, gated on their sources)
otp_* — unchanged: the existing tables and state machine are correct
```
Constraints do the work: `event_id` unique ⇒ no duplicate intent from a replayed event (brief §15.3);
`dedup_key NOT NULL` ⇒ no silent NULL hole (C-10); composite `provider_message_id` uniqueness scoped
by tenant ⇒ receipts cannot cross tenants (C-06). Indexes as the brief lists, plus
`communication_deliveries (tenant_id, notification_intent_id)` and
`(delivery_status, created_at)` for the reconciler's scan.
**Content rule:** no full message bodies, no OTPs, no personal detail in a delivery log — minimal
rendered metadata plus a protected reference. Recipient addresses are referenced, not duplicated per
attempt, and are hashed where only correlation is needed.

---

## 20. API contracts

Existing envelope (`{data, meta}`), existing planes, named rate limiters per surface — the
`throttle:60,1`-style shared counters that ADR-0025 retired for Search are also present on the webhook
routes (`Routes/api.php:170-178`), and those need their own limiter keyed on provider, not IP
(providers retry in bursts; a shared per-IP limiter will drop real receipts).

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/api/v1/notifications` · `/unread-count` · POST `/read` · `/read-all` | **existing**, keep |
| GET/PATCH | `/api/v1/me/notification-preferences` | **new** — the one system (§14); the `CustomerSetting` shadow keys deprecate with a redirect window |
| GET | `/api/v1/me/notification-deliveries` | **new** — per-recipient delivery + receipts (C-16) |
| POST | `/api/v1/otp/send` · `/verify` · `/resend` | **existing**, keep; typed errors, non-enumerating |
| POST | `/api/v1/admin/communication/test-send` | capability-gated; refuses in production unless an explicit override is set (C-01's inverse) |
| POST | `/api/v1/admin/communication/templates/{code}/pause` · `/resume` | §7.5, §10.2 |
| GET | `/api/v1/admin/communication/health` | provider state, outbox depth, queue lag, callback backlog, credits, unmatched receipts (§17.2) |
| POST | `/webhooks/whatsapp/meta` · `/webhooks/msg91/sms` · `/otp` · `/email` | §8 — fail-closed signatures, dedup, ack fast, process async |

Callbacks ack quickly and process asynchronously (the brief's 200 ms target), because a slow handler
invokes provider retries — which is precisely the multiple-callback situation §5.2 guards.

---

## 21. Next.js and package boundaries

| Package | Today | Becomes |
| --- | --- | --- |
| `packages/communication` | 158 LOC policy stub, **zero app importers** | channel/delivery/status **contracts + generated client**; TS routing logic deleted or code-generated from PHP (§3.4) |
| `packages/notifications` | 148 LOC, live-bus only | notification/preference/reminder **contracts**; timeline models |
| `packages/otp` | 943 LOC, real, widget loader in use | unchanged; hardened; typed errors |

Components per the brief's §16.4 list, with the three that do not exist at all flagged as the actual
work: `NotificationCenter`, `NotificationBell`, `DeliveryStatusBadge`,
`PreferencePanel`, `OtpScreen` (already real at `apps/web/components/auth/OtpScreen.tsx:88`),
`PujaConfirmationCard`, `PanditArrivalStatus` (needs §15.2's source),
`StreamingAccessCard`, `PrasadTrackingCard`.
Principles: **the frontend never issues `verified`/`delivered`/`paid`** — it renders Laravel's
authority; no provider secrets client-side (property currently true — add a CI check asserting no
`NEXT_PUBLIC_` var matches a provider credential pattern, so C-15's blind spot cannot recur
silently); OTP input with autocomplete hints, per-channel status labelling, and honest copy —
"delivered" only when a receipt exists, never inferred from an accepted request.

---

## 22. Work breakdown

Estimate classes per methodology §1. Scaffold task first per epic.

### E-CM-01 — Stop the platform lying about delivery *(P0; nothing else is trustworthy before this)*

| Task | Title | Est | Key AC |
| --- | --- | --- | --- |
| T-CM01-01 | Remove simulated success; `ProviderNotConfiguredException`; boot guard rejecting `driver=fake` outside local/testing/ci (C-01) | M | AC-1 GIVEN `MSG91_AUTH_KEY` unset WHEN dispatching THEN delivery is `FAILED/provider_unconfigured`, an alert event exists, and **no row is marked sent**. AC-2 GIVEN `NOTIFICATIONS_DRIVER=fake` in production WHEN the app boots THEN startup fails with a named reason. AC-3 the `simulated` state cannot be produced in production and is rejected by the state machine. |
| T-CM01-02 | Timeout + connect/read split + retry-policy separation on every provider call (C-08) | S | AC: a hung provider is bounded by config; a pre-send failure retries and a post-acceptance ambiguity does not. |
| T-CM01-03 | Write `provider_message_id` on send; add `idempotency_key` (C-02) | M | AC: GIVEN a successful provider send WHEN the row is read THEN `provider_message_id` is non-null and matches the receipt join key. |
| T-CM01-04 | Widen `communication_deliveries`: intent id, lifecycle timestamps, attempt count, template version, cost, `reconciled_at` (C-09) | M | AC: every state in §6.1 is representable; a migration test asserts no truncation on the longest state name. |
| T-CM01-05 | `dedup_key NOT NULL` + composition rule, backfill then constrain (C-10) | S | AC: two identical sends with the same composition produce one intent, proven by attempt, not by NULL leniency. |
| T-CM01-06 | `.gitleaks.toml` patterns for MSG91 auth key, WhatsApp token/app secret, DLT ids (C-15) | S | AC: a seeded test secret is caught in pre-commit and CI. |

### E-CM-02 — Webhook integrity and receipt reconciliation
T-CM02-01 fail-closed signature verification for both providers + **invert** the "unsigned webhook
accept" test + forged-signature test (C-07). T-CM02-02 tenant-scoped, guarded receipt application; no
`withoutGlobalScopes()` (C-06). T-CM02-03 `communication_webhook_events` dedup table + unique key.
T-CM02-04 `delivery:reconcile` command for `SENT`/`DELIVERY_UNKNOWN`. T-CM02-05 business-state
firewall test (§18.2). T-CM02-06 named, provider-keyed webhook rate limiters.
AC: a forged receipt cannot change state; a retried callback applies once; an unmatched receipt is
counted, not silent.

### E-CM-03 — Outbox, intents, routing
T-CM03-01 `notification_outbox` + `event_id` UNIQUE + processor. T-CM03-02 `notification_intents`
one-per-event-per-recipient (§4.1). T-CM03-03 channel deliveries derived, never independently decided.
T-CM03-04 **revalidation at dispatch** (§16.2). T-CM03-05 routing matrix as versioned config; delete
or generate the TS duplicate (§3.4). T-CM03-06 per-channel circuit breaker + non-blocking dispatch
(§17.4). T-CM03-07 `ProviderCapabilityRegistry` + template/capability validation (§7.3).
AC: a rolled-back transaction produces **zero** dispatches, proven at the outbox boundary rather than
by a `dispatch()` call.

### E-CM-04 — WhatsApp *(blocked on G-PROV-1 / A-1)*
T-CM04-01 provider wiring with pinned `v21.0` + integration test (A-8). T-CM04-02 template
registry + versioning + lifecycle + **pause propagation** (§10.2). T-CM04-03 category correctness
(no marketing in utility; template-lint step; §9.2). T-CM04-04 opt-in evidence enforced before
proactive send. T-CM04-05 24-hour service-window tracking + free-form rules. T-CM04-06 interactive
card: booking ref, date/time, pandit, secure deep link, support action. T-CM04-07
**`PAYMENT_RECEIVED` vs `BOOKING_CONFIRMED` separated** (§15.1). T-CM04-08 opt-out/suppression
handling incl. "block ⇒ no marketing SMS substitution" (Scenario G). T-CM04-09 quality-rating
monitoring + alert.

### E-CM-05 — SMS + DLT
T-CM05-01 **implement `deliverSms()`** — the routed-but-undelivered path (C-04). T-CM05-02
`sms_dlt_templates` registry; no unregistered fallback (C-11). T-CM05-03 per-purpose templates
(OTP/booking/payment/assignment/reminder/prasad) + Unicode/Hindi flag. T-CM05-04 PE/header/template-id
consistency check before send. T-CM05-05 credits/balance monitoring. T-CM05-06 delivery-vs-acceptance
handled via `DELIVERY_UNKNOWN` + reconcile.

### E-CM-06 — Email, brand tokens, deliverability
T-CM06-01 migrate `Mail::to()->send()` behind the provider interface **preserving claim-row
idempotency** (C-03). T-CM06-02 **brand token system + CI computed-contrast check** with the §12.2
table, incl. the Flame-on-Ivory prohibition (C-12). T-CM06-03 responsive HTML + inline CSS +
**`text/plain` alternative** + preheader. T-CM06-04 per-client validation matrix with recorded
screenshots. T-CM06-05 SPF/DKIM/DMARC + `List-Unsubscribe` + separate transactional/marketing
streams (A-4). T-CM06-06 from-identity config validation, replacing the stock
`hello@example.com` default. T-CM06-07 bounce/complaint suppression. T-CM06-08 `kind`-specific
subjects (C-17). T-CM06-09 receipt + invoice email, gated on E-CM-07.

### E-CM-07 — Financial documents *(blocked on A-6 / G-INV-0)*
T-CM07-01 supplier-model determination recorded as config-data, not a template constant. T-CM07-02
immutable snapshot at issue. T-CM07-03 atomic financial-year serial allocator per issuing entity, with
gap detection. T-CM07-04 Rule 46 particulars validation. T-CM07-05 PDF toolchain decision + install.
T-CM07-06 credit/debit notes; no mutation of issued documents. T-CM07-07 alternative delivery when
email is suppressed (§13.1.6). T-CM07-08 effective-dated e-invoicing config, nothing hardcoded.

### E-CM-08 — Consent convergence
T-CM08-01 extend `consent_ledger` purposes + channel × purpose state. T-CM08-02 `NotificationPolicy`
evaluated at dispatch (§14.3) — AC: **dispatch outcome changes when the ledger changes**.
T-CM08-03 retire `CustomerSetting` shadow keys with an explicit redirect window. T-CM08-04 unify
`PreferencesReader` and `notification_preferences`. T-CM08-05 recipient-entitlement check incl.
family-member scoping (§18). T-CM08-06 notice/consent versioning. T-CM08-07 suppression list +
withdrawal. T-CM08-08 erasure integration against the **shared** privacy kernel
(`docs/onboarding/SPEC.md` §10) — no second system. T-CM08-09 quiet hours + IANA timezone + clamp.

### E-CM-09 — Journey event producers *(blocked on A-5 / G-EVT-0)*
T-CM09-01 pandit assignment/departure/arrival/delay/no-show event sources — **decision first**:
partner-app action, coordinator, or scheduled rule (§15.2). T-CM09-02 arrival ETA source + materiality
threshold + no-GPS-exposure default. T-CM09-03 `payment.received`/`refunded` producers from Payment.
T-CM09-04 streaming session events + per-recipient tokens + revocation (§15.3). T-CM09-05 digital
artifact pipeline + private storage + signed links + imagery permission (§15.4). T-CM09-06 prasad
shipment lifecycle + carrier source (A-7) + `(consignment,status)` dedupe (§15.5). T-CM09-07
`booking.reminder_due` scheduler + revision binding. T-CM09-08 booking
`EXPIRED`/`PENDING_PAYMENT` events.

### E-CM-10 — Operations, cost, surfaces
T-CM10-01 metrics + cost accounting from versioned rate cards. T-CM10-02 provider health checks +
real dashboard + alerts (§17.2/17.3). T-CM10-03 delivery-status surfaces for staff and recipients
(C-16). T-CM10-04 admin test-send (non-prod only), template pause/resume. T-CM10-05 module extraction
scaffolds: `Communication` / `Notifications` / `Otp` with manifests, permissions, events, routes,
Deptrac layers, and the `Customers` relocation with tests moved intact (§3).

**Sequencing note.** E-CM-01…03 are **not** feature work and should ship before any new journey
template. C-01 is one guard clause; without it, every subsequent stage is unverifiable, because the
system's own reports cannot be trusted.

---

## 23. Acceptance gates

| Gate | Blocks | Evidence |
| --- | --- | --- |
| **G-TRUTH-0** | **every other gate** | C-01 closed: unconfigured provider cannot produce a success state; `driver=fake` cannot boot in production; a test asserts each. **Nothing about delivery is trustworthy before this.** |
| **G-LOOP-1** | delivery-status features | `provider_message_id` written on send, receipts applied tenant-scoped through the state guard, and **measured** unmatched-receipt rate ≈ 0 in staging |
| **G-SIGN-1** | webhook endpoints live | fail-closed signatures on both providers; forged-signature and replay tests green; the old "unsigned webhook accept" test inverted |
| **G-IDEM-1** | SMS + WhatsApp | duplicate suppression proven per channel (today only in-app/email have it, C-08); ambiguous results reconcile rather than resend |
| **G-SCHEMA-1** | §6.1 lifecycle | all states representable without truncation; guards enforced by illegal-edge tests |
| **G-CONSENT-1** | any proactive send | single preference/consent system evaluated **at dispatch**, with a test whose outcome flips when the ledger changes; marketing never substitutes after a WhatsApp block |
| **G-PRIV-1** | journeys containing personal detail | recipient-entitlement enforced (family-member scoping); §4.3 data-minimisation list is a lint or schema rule, not prose |
| **G-PROV-1 / 2 / 3** | E-CM-04 / §11 / providers | A-1 setup evidence with a live test send; A-2 OTP-product confirmation incl. the no-app-known-code constraint (already honoured by construction); pinned API-version integration tests |
| **G-DLT-1** | any SMS | registered PE/header/template per selectable code; no unregistered fallthrough; template approval state read at dispatch |
| **G-MAIL-1** | invoice/receipt email | SPF+DKIM+DMARC verified, separate streams, `List-Unsubscribe`, plain-text alternative present, contrast check green |
| **G-EVT-0** | §15 feature parity claims | every journey event has a named producer with a test; **no template ships for an event nothing emits** (C-14) |
| **G-INV-0** | E-CM-07 | supplier determination recorded; snapshot immutability, atomic FY serials, Rule 46 particulars asserted by tests |
| **G-FIN-0** | webhook/inbound handling | no messaging surface can change payment, booking or invoice state — asserted, not documented |
| **G-OBS-1** | "notifications work" claims | latency by stage (event→intent→submit→accept→deliver→read), cost per message, per-provider health measured on real traffic |
| **G-TENANT-0** | every epic | zero cross-tenant delivery reads/writes; `withoutGlobalScopes()` absent from delivery paths |
| **G-REL-1** | production | provider outage never blocks booking or payment; circuit-breaker and parked-intent tests pass; kill switch works per channel and per template |
| **G-SEC-1** | secrets | `.gitleaks.toml` covers both providers; CI asserts no `NEXT_PUBLIC_*` matches a credential pattern |

**Release train.** *Step 1* = E-CM-01…03 (truthful failures, closed receipt loop, outbox) — no new
channels, ships alone and materially improves safety. *Step 2* = E-CM-06 email + G-MAIL-1 + brand
tokens. *Step 3* = E-CM-05 SMS/DLT, once G-DLT-1. *Step 4* = E-CM-04 WhatsApp, once G-PROV-1.
*Step 5* = E-CM-07 invoices (A-6) then E-CM-09 journeys in value order: confirmation → payment →
assignment → **arrival, once its event source exists** → completion → prasad → streaming.
*Step 6* = E-CM-08/10 convergence and operations, with E-CM-08's consent convergence landed *before*
marketing volume exists.

---

## 24. Risks and kill criteria

| # | Risk | Control | Kill / revert |
| --- | --- | --- | --- |
| R-01 | Fabricated success recurs (a new adapter returns a fake ok) | boot guard + non-producible `simulated` state + alert | a provider returning success without a provider reference fails CI |
| R-02 | Duplicate messages during retries/reconciliation | four-layer idempotency (§5.2) + ambiguity ⇒ reconcile | duplicate rate above threshold ⇒ channel disabled, not resend |
| R-03 | Template misclassification ⇒ Meta policy or pricing problem | category as a required attribute + lint + approved-template-only | rejected/paused template auto-stops; miscategorised ⇒ template pulled, not reworded free-form |
| R-04 | Opt-in evidence thin or missing | recorded at capture, required before proactive send, re-checked at dispatch | no opt-in record ⇒ no send; channel disabled |
| R-05 | WhatsApp quality degrades → limits/suspension | read/opt-out/block monitoring, throttles, kill switch | quality threshold breached ⇒ proactive sends disabled |
| R-06 | DLT mismatch surfaces only as carrier rejection | registry check pre-send + reason codes + reconciliation | unregistered template ⇒ `POLICY_BLOCKED`, never a silent fallthrough (C-11) |
| R-07 | A webhook payload drives business state | §18.2 firewall asserted by a test | any handler able to change business state is blocked from merge |
| R-08 | Notification leaks private devotional/personal detail | §4.3 minimisation as schema/lint, recipient entitlement, no identifiers in URLs | a notification containing a blocked field fails CI |
| R-09 | Streaming/media links leak | per-recipient short-lived tokens, revocation, access logging, no public recording URL | a leak report ⇒ token rotation + journey disabled |
| R-10 | Invoice wrong: issuer, serial, particulars | A-6 model as data, immutable snapshot, atomic FY serials, particulars validation | a document asserting the wrong supplier is a **legal** incident, not a bug; generator disabled until reviewed |
| R-11 | Provider outage blocks transactions | async dispatch + breaker + park/drain | a provider in a transaction path is a blocker |
| R-12 | Notification fatigue → blocks and unsubscribes | frequency caps, batching, per-recipient thresholds, quiet hours | opt-out rate above baseline ⇒ schedule reduced; "post-completion follow-up" off by default |
| R-13 | Arrival alerts depend on non-existent telemetry | §15.2: no ETA without a recorded source | a fabricated ETA in a message is a blocker, not a nice-to-fix |
| R-14 | Module extraction breaks the working OTP path | extraction is a move, tests move intact, OTP tables untouched | any OTP regression ⇒ revert the extraction, keep the fix |
| R-15 | Three preference systems re-fragment | one `NotificationPolicy` + deprecated shadow keys | a new `notify_*` config key without a policy check is rejected |
| R-16 | Cost surprises from volume or category | rate cards, alerts, per-tenant budgets, cost-aware routing | unexpected spike ⇒ template disabled pending review |

**Verdict.** Strong product direction, genuinely achievable, and the brief's target — *the
end-to-end devotional journey made reliable, traceable, private and respectful* — is the right
ambition. The gap between that ambition and today's code is narrower than it looks in §1.2, and
wider in kind than it looks in §1.1.

**Sequencing claim.** Two guard clauses and one written column close the largest trust gap in this
document: refuse to send on missing credentials (T-CM01-01), write `provider_message_id` when a
provider accepts a message (T-CM01-03), and verify signatures without a fallback branch
(T-CM02-01). Every other improvement — DLT registries, brand tokens, arrival journeys, invoices — is
better engineering. Those three are what makes the platform's own reports mean something.

---

## 25. ADRs required before approval

| ADR | Subject | Must record |
| --- | --- | --- |
| ADR-0050 | Delivery kernel extraction from `Customers`; no provider calls from business modules | §3 boundary, Deptrac layers, the preserved OTP/email-idempotency patterns |
| ADR-0051 | Fail-closed provider configuration | the `ProviderNotConfiguredException` contract, fake-driver boot guard, the `simulated` state's non-producibility (C-01) |
| ADR-0052 | Outbox + four-layer idempotency + ambiguous-result reconciliation | §5, why at-least-once is the contract, reconcile-not-resend |
| ADR-0053 | Webhook trust: signatures, tenant scoping, state guards, business-state firewall | §8, inverting the current fail-open and `withoutGlobalScopes` behaviour |
| ADR-0054 | Template governance + DLT registry as pre-send requirements | §10/§11, category correctness, pause propagation, no unregistered fallback |
| ADR-0055 | Brand tokens in email with computed contrast enforcement | §12.2 table, the Flame-on-Ivory prohibition, deliverability/domain separation |
| ADR-0056 | Consent convergence onto the shared privacy kernel | §14, cross-reference Onboarding ADR-0047, evaluation at dispatch, the retired shadow keys |
| ADR-0057 | Invoicing supplier identity, snapshot immutability, FY serialisation | §13, recording A-6's legal determination as a prerequisite and data, never a template constant |
| ADR-0058 | Journey event producers (arrival, streaming, prasad) as prerequisites, not templates | §15, G-EVT-0, and the no-fabricated-ETA / no-false-live-link rules |

Also required on merge: module manifests for the three new modules (permissions incl.
`communication:test:send`, `templates:manage`, `delivery:view`, `consent:manage`, `webhook:replay`
sensitive-gated; events incl. `notification.intent.created`, `communication.delivery.receipted`,
`otp.challenge.*`); `@navdhya/api-contracts` types for the §20 endpoints and a contract test keeping
them aligned; a provider-capability matrix section in `docs/15-integrations`; and the CI checks in
C-15/§21.

---

## 26. Verification (this document)

1. Every `C-` row cites a `file:line` that still resolves; **(unverified)** items are not used as
   requirements.
2. Every epic in §22 maps to at least one gate in §23; every gate has a test that **fails when the
   enforcement is absent** (§1.3's escalation, applied to this document itself).
3. Every AC written into `docs/specs/tasks/` follows methodology §3 — observable, decidable,
   CI-testable, falsifiable; "appropriate", "reasonable", "user-friendly" do not appear.
4. Every task touching tenant data carries a tenancy AC; every task touching a provider credential or
   webhook carries a negative AC; every task touching messaging content carries a
   data-minimisation AC.
5. No consent, preference, template or routing logic is implemented twice in PHP and TS; no provider
   is reachable from a business controller.
6. No metric, latency, cost or deliverability number in this document is presented as achieved; the
   contrast ratios are computed here (§1.2 C-12, §12.2), and everything else is a target pending
   measurement.
7. No external dependency is described as optional or avoidable that the §2.3 boundary marks as
   necessary.
8. Doc drift is a review blocker; a behaviour change without a change here fails DoD item 7.