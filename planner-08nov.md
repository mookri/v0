
 Here is Qoder's plan:

────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 Booking lifecycle, financial operations and tenant billing implementation plan

 1. Context and confirmed scope

 Navdhya has a working Laravel booking/payment slice, but not a complete booking-to-settlement system. The objective is
  to make booking, fulfillment, payments, taxes, invoices, refunds, settlements and tenant subscriptions consistent,
 recoverable, auditable and tested across customer, partner and operator applications.

 Confirmed requirements:
 - Countries: India, United Kingdom, United Arab Emirates, and United States—all 50 states plus DC. US territories are
 not included.
 - Legal selling model: configurable per tenant, with an immutable model/terms snapshot on each transaction.
 - Billing: customer transactions and recurring platform subscriptions, including tenant-facing controls.
 - Minimal dependencies: reuse the existing infrastructure; do not introduce an ERP framework, workflow engine, payment
  SDK, tax SDK, message broker or second authoritative backend.
 - Deliver complete vertical slices with explicit acceptance criteria, not disconnected interfaces or mock-only pages.

 This is an implementation plan, not a compliance certification. Code was inspected; tests, migrations, live payments
 and browser flows have not been executed during planning.

 Scope boundaries

 Include one scheduled ritual/service booking with associated service/kit/fee lines; its complete lifecycle;
 provider/temple capacity and booked-kit stock; monetary adjustments; transaction documents and accounting;
 provider/vendor payouts; payment disputes and reconciliation; recurring tenant SaaS billing; operational and finance
 controls.

 Do not expand into payroll, purchasing/manufacturing, customer ritual subscriptions, gift cards, a generalized
 shopping cart, wallets, credit lending or unrelated ERP modules. One booking may require multiple documents because of
  legal seller or tax rules, but cannot mix collection currencies. Cross-tenant resource pooling is not implicitly
 supported: each inventory/calendar resource has one authoritative scheduling owner.

 Business/compliance activation gates

 Engineering can proceed with clearly synthetic fixtures. Production activation requires these approved records, not
 guessed defaults:
 ┌────────────────┬─────────────────────────────────────────────────────────────────┬────────────────────────────┐
 │ Gate           │ Required approval/evidence                                      │ Blocks                     │
 ├────────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────┤
 │ G1 — Seller    │ Tenant's principal/agent status; identified service seller,     │ Live checkout/document     │
 │ and collection │ invoice issuer, collector and payout beneficiary; authority to  │ issuance for that model    │
 │ model          │ invoice for another seller; registrations and approved          │                            │
 │                │ collection arrangements                                         │                            │
 ├────────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────┤
 │ G2 — Tax       │ Effective classifications, sourcing/tax-point rules,            │ Affected taxable           │
 │ coverage       │ registration/nexus obligations, exemptions,                     │ transaction types; unknown │
 │                │ reporting/withholding rules and authoritative jurisdiction/rate │ is never silently zero     │
 │                │ data for each enabled country/locality                          │                            │
 ├────────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────┤
 │ G3 —           │ Cancellation/reschedule/no-show rules, provider compensation,   │ Automatic monetary         │
 │ Commercial     │ commissions, fees, refund review thresholds and settlement      │ consequences               │
 │ policies       │ timing                                                          │                            │
 ├────────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────┤
 │ G4 — SaaS      │ Plan prices/intervals, trials, proration, grace/dunning,        │ Live recurring billing     │
 │ contract       │ cancellation/refund and access-suspension terms                 │                            │
 ├────────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────┤
 │ G5 — Provider  │ Merchant accounts, supported countries/currencies, mandate      │ Unsupported                │
 │ capability     │ consent, refund/payout support, recovery guarantees and         │ collection/payout methods  │
 │                │ regulatory/account onboarding                                   │                            │
 └────────────────┴─────────────────────────────────────────────────────────────────┴────────────────────────────┘
 The existing cancellation page leaves 48–72 hours undefined and overlapping boundaries unclear
 (apps/web/app/(legal)/cancellation-policy/page.tsx:60). Do not translate that copy directly into executable rules.
 Approval must produce exhaustive, non-overlapping time bands and updated customer-facing terms. Legal exemptions for
 religious services must never be inferred from the service name.

 For nationwide US support, the deliverable includes a 51-jurisdiction coverage matrix plus applicable local taxing
 jurisdictions, not a country-level rate. A reviewed, maintained data source and its usage rights are required. Begin
 with versioned validated data imports; a paid data/tax service is a separate approval decision if necessary for
 reliable nationwide coverage, not an implicit dependency.

 2. Current system: verified baseline and concrete gaps

 The active backend is Laravel 12, MySQL 8, shared/dedicated tenant databases, database queues, transactional outbox,
 audit and capability-based authorization. Some root architecture prose describes an older PostgreSQL/TypeScript
 design; executable code takes precedence.
 ┌────────────────────────────────────────────────────────────────┬────────────────────────────────────────────────┐
 │ Evidence                                                       │ Existing behavior and required correction      │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ platform-navdhya/app/Modules/Booking/Http/Controllers/Reservat │ Creation, idempotency, transitions and events  │
 │ ionController.php:123                                          │ exist. The hash omits inline address data;     │
 │                                                                │ uniqueness-race handling does not recheck the  │
 │                                                                │ winning payload. Address-ID resolution is      │
 │                                                                │ TODO; duration is client-supplied.             │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ Same controller :332, :629                                     │ Cancel/reschedule validate stale objects       │
 │                                                                │ before transactions; they do not lock/recheck  │
 │                                                                │ all guards within the mutation. Cancellation   │
 │                                                                │ does not release assignments/resources or      │
 │                                                                │ initiate refunds. Some audits happen after     │
 │                                                                │ commit.                                        │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ Same controller :402;                                          │ Checkout preserves a committed provider order  │
 │ platform-navdhya/app/Modules/Payment/PaymentIntents.php:27     │ across a subsequent booking failure, but calls │
 │                                                                │ the provider before durably recording the      │
 │                                                                │ operation. A retry can reuse an old intent     │
 │                                                                │ while recording a newly calculated booking     │
 │                                                                │ total.                                         │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ platform-navdhya/app/Modules/Payment/Models/PaymentIntent.php: │ Payment is pinned to tenant_shared; booking    │
 │ 51; platform-navdhya/app/Modules/Booking/Models/Booking.php:55 │ follows tenant routing. Atomicity is not       │
 │                                                                │ guaranteed after tenant promotion.             │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ platform-navdhya/app/Modules/Payment/Http/Controllers/PaymentW │ HMAC checks exist. Deduplication relies on     │
 │ ebhookController.php:79, :152, :198                            │ status; partial refunds are ignored; money     │
 │                                                                │ outcomes are discarded when booking refuses a  │
 │                                                                │ transition, including capture after            │
 │                                                                │ cancellation.                                  │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ platform-navdhya/app/Modules/Booking/Domain/BookingLifecycle.p │ Payment/fulfillment states are mixed, and the  │
 │ hp:36;                                                         │ refund handler's allowed transitions disagree  │
 │ platform-navdhya/app/Modules/Booking/BookingPaymentTransitions │ with the declared matrix.                      │
 │ .php:70                                                        │                                                │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ platform-navdhya/app/Modules/Pandit/PanditAssignmentManager.ph │ Availability checks precede locking;           │
 │ p:37, :138, :236                                               │ completion synthesizes a start; unassignment   │
 │                                                                │ deletes history. Search results are not        │
 │                                                                │ resource reservations.                         │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ platform-navdhya/app/Modules/Catalog/QuoteCalculator.php:26    │ Effective SKU/pandit/market/currency pricing   │
 │                                                                │ exists; no authoritative tax determination.    │
 │                                                                │ Selected pandit is also lost by                │
 │                                                                │ apps/customer/application/bookings/confirm-boo │
 │                                                                │ king-draft.ts:16.                              │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ apps/customer/app/api/bookings/[bookingId]/reschedule/route.ts │ BFF fetches the newest version instead of      │
 │ :51                                                            │ submitting the version the user actually       │
 │                                                                │ reviewed.                                      │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ platform-navdhya/app/Http/Controllers/Ops/BillingController.ph │ Only plan metadata and empty invoice history.  │
 │ p:24                                                           │ Finance/tax/subscription specifications and TS │
 │                                                                │ helpers are not a persisted, wired financial   │
 │                                                                │ system.                                        │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ platform-navdhya/app/Platform/Tenancy/Console/TenantProvisionS │ Provisioning clones missing tables; it does    │
 │ chemaCommand.php:37                                            │ not upgrade existing dedicated schemas.        │
 ├────────────────────────────────────────────────────────────────┼────────────────────────────────────────────────┤
 │ e2e/playwright.config.ts:18; docs/development/TESTING.md:158   │ E2E selects auth/console smoke, not booking    │
 │                                                                │ lifecycle. Backend CI is dormant in a nested   │
 │                                                                │ directory; root API tests are not fully gated. │
 └────────────────────────────────────────────────────────────────┴────────────────────────────────────────────────┘
 Preserve existing security work, tenant isolation, idempotency persistence and the intent-survives-booking-failure
 regression. Change incorrect behavior with explicit replacement tests, not by deleting assertions indiscriminately.

 3. Recommended architecture and ownership

 Keep the modular monolith. Extract use-case services from large controllers only where needed for these workflows;
 controllers handle authorization, validation and response envelopes.
 ┌────────────────────┬────────────────────────────────────────────────────────────────────────────────────────┐
 │ Owner              │ Responsibility                                                                         │
 ├────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Existing Booking   │ Booking revisions, operational state, commands, reschedule/cancellation orchestration, │
 │                    │ resource allocation records and customer timeline                                      │
 ├────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Existing Pandit,   │ Provider eligibility/calendars, temple capacity, vendor stock and fulfillment; expose  │
 │ Temple, Vendor     │ contracts to Booking, never mutate booking rows directly                               │
 ├────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Existing Catalog   │ Service definitions, duration/variants, price matrix and price version references      │
 ├────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Existing Payment   │ Payment intent/attempts, durable provider operations, captures, refunds, disputes,     │
 │                    │ verified event processing and payment status                                           │
 ├────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ New Tax module     │ Registrations, classifications, approved country-specific determination, tax snapshots │
 │                    │ and tax-report exports                                                                 │
 ├────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ New Finance module │ Immutable invoices/credit/debit notes, limited double-entry subledger, allocations,    │
 │                    │ receivables/payables, payouts and reconciliation                                       │
 ├────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ New Billing module │ Tenant SaaS contracts, plan prices, renewal cycles, proration and dunning; reuse       │
 │                    │ Payment/Tax/Finance                                                                    │
 ├────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Existing platform  │ Tenant routing, authorization/MFA/SoD, audit, outbox, scheduler/queues and central     │
 │ kernel             │ entitlement projections                                                                │
 └────────────────────┴────────────────────────────────────────────────────────────────────────────────────────┘
 Reuse ListsCatalogItems, CalculatesQuotes, CreatesPaymentIntents, ReadsBookings, TransitionsBookingPayment and
 TransitionsBookingFulfillment, evolving their contracts rather than bypassing them. Add narrow contracts for
 addresses, resources, tax determination and document issuance. Extend module manifests/API fragments and Deptrac rules
  with each slice.

 Authoritative money and lifecycle rules execute in PHP. Reuse useful TS fixture expectations and contracts, but do not
  wire the parallel TS finance/payment implementations into a second production write path. Customer/partner apps
 consume server-computed totals and allowed actions.

 Database boundaries

 - Booking, Payment, Finance, Tax, resource records, domain audit and outbox for an operating tenant reside on that
 tenant's resolved connection. Same-transaction guarantees apply only there.
 - A platform subscription is a sale by the platform: persist its financial records in the platform seller's
 billing-tenant context, with billed_tenant_id identifying the subscriber. Never put platform revenue in the customer's
  operating ledger.
 - Central storage holds tenant/plan/entitlement metadata and minimal verified provider routing/inbox metadata. It is
 not a second financial ledger.
 - Central/tenant updates communicate through durable, idempotent messages. No distributed transaction claim,
 cross-database foreign key or synchronous dual-write assumption.
 - Billing-tenant access is internal and capability-scoped; a subscriber receives only its own subscription and
 document projection.

 4. Data structures and invariants

 Common representation

 - ULIDs for new domain identifiers; preserve existing user ID types. Include explicit tenant_id on every tenant-owned
 table.
 - UTC DATETIME(6) instants; store the booking's IANA timezone and original local-time/offset choice. Intervals are
 half-open [start, end).
 - Signed BIGINT integer minor units, CHAR(3) currency, explicit exponent; nonnegative constraints for charges and
 quantities. Exact rational/decimal rates, never floats.
 - Monetary API fields use canonical integer strings where JavaScript safety cannot be guaranteed. Checked conversion
 at provider boundaries; reject overflow, do not silently round.
 - Optimistic version, timestamps, source/correlation IDs and immutable snapshot hashes. Database uniqueness enforces
 identities; same-tenant composite references prevent accidental cross-tenant associations.
 - Financial history is append-only. JSON is for validated immutable snapshots/evidence, not opaque mutable balances or
  arbitrary executable rules.

 platform-navdhya/app/Support/Money.php:20 uses a four-decimal internal scale; do not confuse it with currency minor
 units. Introduce a narrowly scoped minor-unit value/rounding contract for booking finance with explicit conversion
 where needed; reuse existing BCMath capability only after checking/declaring the runtime requirement. Do not change
 unrelated callers or add a monetary package.

 Tables to extend or introduce

 Names below are implementation targets; extend existing stores where noted instead of creating duplicates.
 ┌─────────────────────────────────┬───────────────────────────────────────────────────────────────────────────────┐
 │ Structure                       │ Required fields and database rules                                            │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ Extend bookings                 │ Operational status/version; current revision; service start/end/timezone;     │
 │                                 │ checkout expiry; accepted quote; linked original booking for rebooking.       │
 │                                 │ Retain legacy fields through verified cutover only.                           │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ booking_revisions,              │ Unique (tenant_id, booking_id, version); SKU/variant/quantity, server         │
 │ booking_lines                   │ duration, selected provider/temple, market, seller assignment, address and    │
 │                                 │ service-mode snapshots, quote/policy versions. One current revision; prior    │
 │                                 │ accepted revisions immutable.                                                 │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ booking_commands                │ Actor, verb, target, idempotency key, canonical request hash, outcome/result  │
 │                                 │ reference; unique (tenant_id, actor_id, verb, idempotency_key). Different     │
 │                                 │ target/payload with same key is 409. Preserve identity for the relevant       │
 │                                 │ financial/retry retention window.                                             │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ Extend booking_transitions; add │ Immutable from/to/version, actor, reason and correlation; unique              │
 │ booking_changes                 │ command/event identity. Changes store proposed reschedule/cancellation,       │
 │                                 │ old/new quote, resource and money consequences, expiry, approval and          │
 │                                 │ application status.                                                           │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ resource_calendars,             │ Stable unique tenant/resource parent for locking. Booking/revision, resource, │
 │ resource_allocations            │ interval, units, HELD/COMMITTED/RELEASED/EXPIRED, expiry and release reason.  │
 │                                 │ Index (tenant, resource, status, start, end) and (tenant, status,             │
 │                                 │ expires_at).                                                                  │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ Extend assignments and          │ Assignment lifecycle/version and immutable release/reassignment history.      │
 │ vendor_inventories; add stock   │ Reuse existing (tenant_id,vendor_id,sku) stock row; add reserved/version      │
 │ reservations                    │ counters and linked reservation/consumption/release records. Enforce reserved │
 │                                 │ <= on_hand; never reserve nonexistent stock.                                  │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ commercial_profiles,            │ Tenant operator, principal/agent model, identified seller/issuer/collector,   │
 │ commercial_policy_versions      │ registration references, billing/commission/payout rules, approval and        │
 │                                 │ effective interval. Immutable published versions; invoice-on-behalf authority │
 │                                 │ recorded.                                                                     │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ price_quotes, price_quote_lines │ Source/revision, price versions, seller/collector, market, currency,          │
 │                                 │ subtotal/discount/fee/tax/total, expiry, terms/hash and acceptance. Accepted  │
 │                                 │ snapshot unique per priced obligation.                                        │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ tax_registrations, tax_codes,   │ Seller/jurisdiction registrations and effective dates; line classifications;  │
 │ tax_rule_versions,              │ typed country rule inputs; effective rate components, source provenance,      │
 │ tax_rate_components             │ approval and version. Publishing rejects ambiguous overlaps.                  │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ tax_computations, tax_evidence, │ Canonical input/output, resolved authority codes, tax point, source           │
 │ tax_dataset_versions            │ dataset/version, rule IDs, component amounts, rounding, exemption evidence    │
 │                                 │ and hash. Coverage/expiry metadata and review history.                        │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ Extend payment_intents          │ Resolved tenant connection and explicit scopes;                               │
 │                                 │ source_type/source_id/source_revision/charge_purpose, quote,                  │
 │                                 │ account/environment, immutable amount/currency, collection summary. Unique    │
 │                                 │ charge identity replaces unique booking-only identity after backfill. A retry │
 │                                 │ is not a new charge.                                                          │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ payment_operations,             │ Operation kind, immutable request/hash, intent/refund/payout reference,       │
 │ payment_attempts                │ stable provider reference,                                                    │
 │                                 │ PREPARED/DISPATCHING/PENDING/UNKNOWN/SUCCEEDED/FAILED, lease/fencing version, │
 │                                 │ attempts and recovery timestamps. Persist preparation before network calls.   │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ payment_captures,               │ Provider IDs unique per provider/account/environment; amount/currency,        │
 │ payment_refunds,                │ original payment and source facts. Refund requests include reserved amount,   │
 │ payment_disputes                │ approval, original line/tax allocation and status. Facts are distinct from    │
 │                                 │ requested operations.                                                         │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ Central                         │ Durable operation-to-tenant routing before dispatch; unique                   │
 │ provider_operation_routes,      │ provider/account/environment/object reference; unique provider event ID,      │
 │ provider_object_routes,         │ raw-body hash, receipt timestamp, processing/routing status and replay        │
 │ provider_event_inbox            │ metadata. Conflicting same-ID payload is an incident.                         │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ finance_documents,              │ Kind (invoice, credit note, debit note, receipt), seller/issuer/buyer         │
 │ finance_document_lines,         │ immutable snapshots, source, tax point, original document/line links,         │
 │ finance_sequences               │ currency, totals, number/series/fiscal period. Unique source purpose; unique  │
 │                                 │ (issuer, series, period, number) in its owning database.                      │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ finance_books, finance_periods, │ Legal book/currency, account, debit/credit minor amounts, counterparty/source │
 │ ledger_journals, ledger_entries │ purpose, effective date and reversal link. Unique posting                     │
 │                                 │ source/purpose/version; balanced journal required before posting;             │
 │                                 │ closed-period guard.                                                          │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ payment_allocations,            │ Capture-to-document allocations; original obligations, commissions,           │
 │ settlement_obligations,         │ withholding, adjustments, holds and beneficiary;                              │
 │ payouts, payout_lines           │ requested/approved/provider-settled payout lifecycle. Unique                  │
 │                                 │ allocation/source and payout operation identities. Extend existing vendor     │
 │                                 │ payout read model rather than maintaining conflicting totals.                 │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ reconciliation_imports,         │ Provider/bank statement identity and hash, source line identity,              │
 │ reconciliation_lines,           │ gross/fee/tax/net/currency, matched facts and unresolved reasons. Same        │
 │ reconciliation_cases            │ statement cannot post twice; adjustments need approval, never silently write  │
 │                                 │ off differences.                                                              │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ billing_plan_prices,            │ Existing central plan reference plus immutable price/terms version; billed    │
 │ billing_subscriptions,          │ tenant/platform seller, anchor/timezone, period, contract/collection status,  │
 │ billing_cycles,                 │ cancel-at-period-end; unique (subscription, period_start, period_end,         │
 │ billing_changes,                │ charge_purpose). Mandate reference/consent/status only—no raw card/bank       │
 │ billing_mandates                │ secrets.                                                                      │
 ├─────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
 │ outbox_deliveries,              │ Unique (event_id, consumer_id) durable delivery with retry/ack state; unique  │
 │ processed_events, entitlement   │ (consumer, event_id) applied in the destination's mutation transaction.       │
 │ projection records              │ Central projection includes source subscription version and expiry; stale     │
 │                                 │ events cannot overwrite newer entitlements.                                   │
 └─────────────────────────────────┴───────────────────────────────────────────────────────────────────────────────┘
 Every new monetary balance is derived from immutable facts/postings or maintained as a transactionally checked
 projection. No independent mutable total that cannot be reconstructed.

 5. Booking lifecycle and resource behavior

 Target operational transition matrix

 Keep useful existing vocabulary; remove PAYMENT_CONFIRMED and REFUNDED from the new operational contract because they
 describe money, not fulfillment.
 ┌───────────────────────┬───────────────────────────────┬────────────────────────────────────────────────────────┐
 │ From                  │ Allowed destination/command   │ Required guards/effects                                │
 ├───────────────────────┼───────────────────────────────┼────────────────────────────────────────────────────────┤
 │ DRAFT                 │ PENDING_PAYMENT via checkout; │ Full validated revision, accepted valid quote and      │
 │                       │ CANCELLED; EXPIRED            │ resource hold for checkout; no provider order for an   │
 │                       │                               │ invalid booking                                        │
 ├───────────────────────┼───────────────────────────────┼────────────────────────────────────────────────────────┤
 │ PENDING_PAYMENT       │ CONFIRMED; CANCELLED; EXPIRED │ Confirmation requires the priced obligation funded (or │
 │                       │                               │ approved zero-total outcome), valid allocation and     │
 │                       │                               │ unexpired terms. Payment failure remains retryable     │
 │                       │                               │ while the hold is valid                                │
 ├───────────────────────┼───────────────────────────────┼────────────────────────────────────────────────────────┤
 │ CONFIRMED             │ ASSIGNED; CANCELLED; FAILED   │ Verified provider accepts a viable allocation;         │
 │                       │                               │ cancellation/failure releases resources with           │
 │                       │                               │ policy-based financial adjustment                      │
 ├───────────────────────┼───────────────────────────────┼────────────────────────────────────────────────────────┤
 │ ASSIGNED              │ CONFIRMED on unassignment;    │ Authorized explicit start; unassignment preserves      │
 │                       │ IN_PROGRESS; CANCELLED;       │ history and returns to reassignment queue; approved    │
 │                       │ NO_SHOW; FAILED               │ no-show timing/evidence                                │
 ├───────────────────────┼───────────────────────────────┼────────────────────────────────────────────────────────┤
 │ IN_PROGRESS           │ COMPLETED; CANCELLED; FAILED  │ Explicit completion; post-start cancellation/failure   │
 │                       │                               │ is operator-reviewed with compensation and refund      │
 │                       │                               │ assessment                                             │
 ├───────────────────────┼───────────────────────────────┼────────────────────────────────────────────────────────┤
 │ COMPLETED / CANCELLED │ No operational successor      │ Refund, credit, dispute, settlement and correction do  │
 │ / EXPIRED / FAILED /  │                               │ not rewrite fulfillment history; rebooking creates a   │
 │ NO_SHOW               │                               │ new linked booking                                     │
 └───────────────────────┴───────────────────────────────┴────────────────────────────────────────────────────────┘
 All unlisted transitions are rejected. FAILED is operational failure, not one declined payment attempt. Customer
 self-service actions depend on actor, state, observed version, policy, time and financial/resource guards; return
 allowed actions and reason codes from the backend.

 Resource and concurrency protocol

 1. Validate external input and canonicalize every semantic field, including addresses, timezone, provider, quantities
 and accepted quote.
 2. Deduplicate the command. On uniqueness races, compare the winner's hash before replaying it. A transient conflict
 is not a successful command.
 3. Resolve the actual tenant connection. Lock booking/command and stable calendar/stock parents in one documented
 order; acquire multiple resource locks sorted by resource kind/ID. Re-read current occupancy under those locks,
 including the initially empty-calendar case.
 4. Recheck observed version, eligibility, current policy, capacity and deadlines inside the transaction. Version
 increments, state/resource changes, audit and outbox commit together.
 5. Network and notification delivery occur outside those locks. Consumers use durable event identities, not an
 exactly-once delivery assumption.

 Scheduling rules:
 - Search is advisory. Checkout atomically reserves every required resource or reserves nothing.
 - Provider eligibility, service expertise, working windows, exceptions, travel/setup buffers, temple capacity and kit
 stock participate in reservation guards.
 - Customer overlap restrictions use the same interval rules as creation/rescheduling. Bound queries by
 tenant/resource/time rather than loading entire booking histories.
 - Quote and hold deadlines are explicit. A minute-level expiry job releases abandoned holds; payment-in-flight does
 not make a hold immortal.
 - UTC storage plus explicit timezone; reject nonexistent DST times and require offset selection for ambiguous ones.
 Browser-local time is not the service jurisdiction.
 - Admin unassignment/reassignment, provider decline, provider cancellation and no-show preserve event/history records;
  no deletion as a lifecycle shortcut.

 Changes after checkout

 - Cancellation: preview fee, refundable amount and provider compensation from accepted policy; submit with
 version/idempotency. Immediately accepted cancellation atomically releases future resources and creates the financial
 adjustment obligation. A request needing review remains OPEN without falsely telling the customer it is cancelled.
 Approval/rejection is audited.
 - Reschedule: preview and accept a revision-specific change. Retain old allocation while holding the proposed slot.
 Zero/negative price difference can apply atomically with a credit/refund obligation. Positive difference creates a
 distinct charge; apply the new schedule only after verified collection while the new hold is valid. If change expires,
  retain old booking and account for any late collection as refundable/unallocated money. Never lose the original
 appointment on failed collection.
 - Do not mutate an accepted invoice to implement a reschedule. Issue linked credit/debit/replacement documents as the
 approved jurisdictional rules require.
 - Late payment after cancellation/expiry never resurrects a booking or steals a now-occupied slot. Record the money
 and queue the approved refund/reconciliation path.
 - Completion creates a settlement-eligible obligation only once, subject to contractual holds/disputes. Reviews become
  available only for eligible completed service.

 6. Pricing, taxes and legal documents

 Pricing contract

 Use existing QuoteCalculator price selection as the base, preserving exact pandit ULIDs and market/currency. Quote the
  complete server-validated revision: service, variants, quantities, kits, approved discounts, fees and commission
 treatment. Price market and tax jurisdiction are different inputs.

 Quote result includes line bases, discounts, tax-exclusive/inclusive treatment, rate components, fees, total, currency
  exponent, seller/issuer, tax evidence, policy versions, expiry and hash. The displayed quote must be the quote
 accepted at checkout. A changed/expired quote requires explicit reconfirmation; never silently charge a new total.

 Use bounded exact arithmetic, half-up rounding where approved, country-specific rounding policy otherwise, and
 deterministic remainder allocation. For every document: total = taxable/non-taxable line bases - discounts + fees +
 taxes + explicit rounding adjustment, without double-counting inclusive taxes. Commission is not automatically a
 discount on the customer's tax base.

 Jurisdiction implementation matrix
 ┌─────────┬──────────────────────────────────────────────────────────────┬───────────────────────────────────────┐
 │ Package │ Required determination/controls                              │ Mandatory fixture families            │
 ├─────────┼──────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ IN      │ Seller/customer registrations, correct supply classification │ Applicable intra-state/inter-state/UT │
 │         │ and tax point, service location/place of supply; approved    │ cases, domestic/cross-border,         │
 │         │ CGST/SGST/UTGST versus IGST component selection; HSN/SAC;    │ independent treatment of              │
 │         │ registered/unregistered/composition handling;                │ ritual/kit/platform fee/SaaS;         │
 │         │ exempt/zero-rated/reverse-charge outcomes with evidence      │ rate-date boundaries and partial      │
 │         │                                                              │ credit reversal                       │
 ├─────────┼──────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ US — 50 │ Reviewed state/local taxability for each product/service     │ Every state/DC has reviewed           │
 │ states  │ class; destination/origin/service-location sourcing; seller  │ applicable/no-obligation cases;       │
 │ + DC    │ nexus/registration and marketplace collection                │ local/combined/special district cases │
 │         │ responsibility; exemption evidence; address-to-tax-authority │ where relevant; SaaS and kit/service  │
 │         │ resolution; threshold monitoring where required              │ differences; ambiguity, boundary/rate │
 │         │                                                              │ changes and facilitator               │
 │         │                                                              │ responsibility                        │
 ├─────────┼──────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ GB      │ Approved service/goods/SaaS classifications;                 │ B2C/B2B, physical service vs digital  │
 │         │ place-of-supply, customer status and registration evidence;  │ subscription, kit goods,              │
 │         │ VAT outcomes including applicable                            │ domestic/cross-border, original-rate  │
 │         │ domestic/cross-border/exemption/reverse-charge treatment;    │ credits and effective changes         │
 │         │ distinguish Northern Ireland goods cases when applicable     │                                       │
 ├─────────┼──────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ AE      │ Separate UAE VAT classification/place-of-supply/tax-point    │ Service/kit/platform fee/SaaS;        │
 │         │ rules and registrations, domestic/cross-border treatment and │ B2C/B2B, country-specific required    │
 │         │ applicable exemption/zero-rating/reverse-charge evidence     │ evidence and effective changes        │
 └─────────┴──────────────────────────────────────────────────────────────┴───────────────────────────────────────┘
 Not every listed tax treatment is applicable to every transaction: configuration must express the approved result,
 including explicit non-taxability. No guessed statutory rates or nexus thresholds are included in this plan.

 Tax data controls:
 - Typed country rules, not a user-programmable tax language. Locality/rate data may be imported without changing
 engine code.
 - Stage imports with checksum, provenance, effective dates and coverage report; validate referential
 integrity/overlaps; simulate representative invoices; independent approval publishes an immutable version.
 - Use authoritative address/jurisdiction evidence. Never determine US district taxes from country, state or ZIP alone;
  never trust a client-supplied tax jurisdiction/rate.
 - Coverage reports distinguish supported, reviewed_non_taxable/no_collection_obligation, missing, ambiguous and stale.
  Full US acceptance requires reviewed coverage of all 51 state-level jurisdictions and their applicable localities—not
  just 51 passing examples.
 - Future rates may be scheduled; previously accepted snapshots and issued documents never change. Any legal tax-point
 adjustment is a linked new calculation/document, with price-consent treatment defined in G3.
 - Withholding/marketplace collection obligations, including applicable Indian TDS/TCS, are separately configured and
 reported; do not treat withholding as another customer sales-tax percentage.
 - Reports derive from issued/adjusted documents by legal seller and reporting period. Provide reconciled CSV/JSON
 exports. Filing, IRN/e-invoice clearance or other statutory integration is required before activating transactions
 that legally require it; exports alone are not a compliance substitute.

 Invoicing and accounting

 - Separate document issue state from payment state. Drafts have no fiscal number/posting. Issue once, allocate the
 series number under a row lock, and atomically persist document, posting, audit and outbox.
 - Invoice at the approved tax point; customer advances before invoice issuance have their own receipt/liability
 treatment.
 - Split by seller/currency/legal requirements; distinguish seller, invoice issuer, collector and beneficiary. Agent
 collections do not become gross platform revenue.
 - One issuer/series has one authoritative database owner. Where an issuer spans tenants, route issuance to that owner
 or use legally approved disjoint series—never allocate a supposedly global sequence independently in each database.
 - Issued numbers are not reused. Corrections use linked credit/debit notes or a legally permitted documented void;
 preserve the original document and reasons.
 - Partial credits allocate original quantities/base/discount/tax components with deterministic final-remainder
 handling. Total credited/refunded amounts cannot exceed permitted original amounts under concurrent requests.
 - Provide accessible HTML/printable documents and structured exports using existing rendering. Add a server PDF
 dependency only if legally/product-required and explicitly justified; do not add it for convenience.

 Minimum posting templates, approved with worked examples before activation:
 1. Principal sale: receivable against deferred/earned revenue and tax liability at the applicable tax point; capture
 clears receivable or creates a customer advance; performance releases deferred revenue.
 2. Agent collection: gateway clearing against seller payable/customer funds; separately invoice/post the platform's
 fee and its tax. Do not recognize provider gross sales as platform revenue.
 3. Supplier compensation: accrue eligible provider/vendor payable once; preserve commission, withholding, reserve and
 adjustments as separate lines.
 4. Credit/refund: credit note reverses original eligible bases/tax into credit/refund liability; processed provider
 refund clears the liability, not the original service history.
 5. Settlement: reconcile gross collections, refunds/chargebacks, provider fees and fee taxes to net bank settlement;
 payout clears the approved beneficiary obligation only after provider/bank confirmation.
 6. Disputes: record chargeback and reserve/loss/recovery entries independently; a dispute does not automatically
 cancel service or reverse tax.

 Every posted journal balances per legal book/currency, uses unique source/purpose identity, respects closed periods,
 and is corrected by reversal rather than editing. First release uses separately reconciled currency books; no implicit
  FX conversion. Cross-currency settlements require explicit provider FX evidence and approved base-currency/rate
 postings or remain unresolved, not silently netted together.

 7. Durable payments, refunds and reconciliation

 External-operation protocol

 1. Prepare in tenant transaction: lock/validate booking or billing obligation, accepted quote and resource guards;
 persist intent + immutable operation + audit/outbox.
 2. Register routing: durably register the operation's tenant/account/environment centrally, idempotently, before the
 operation may be dispatched. Failure leaves a recoverable prepared operation; it does not call the provider.
 3. Dispatch outside DB transaction: a claimed worker calls the provider with the stable operation reference and a
 genuine idempotency key only where that endpoint documents one. Use bounded connect/read timeouts; disable blind
 create retries.
 4. Persist outcome: success/provider identifiers or definite failure/unknown state, audit and outbox commit on the
 tenant connection. Register returned provider-object routing idempotently; early unmatched webhooks remain replayable.
 5. Apply consequences: Booking/Billing/Finance consume the durable money event using original source/quote identities.
  A failed booking update cannot erase payment evidence or cause a new provider order.

 Gateway outcomes must distinguish a definite rejection from UNKNOWN after timeout/disconnection. Receipt uniqueness is
  not assumed to equal idempotent replay. An expired lease/fencing token prevents stale local writes, but does not
 prove the remote request failed. Recover unknown operations using documented idempotency or authoritative provider
 lookup; otherwise hold for reconciliation instead of creating a second charge/refund/payout.

 The existing outbox drain executes handlers inside a transaction
 (platform-navdhya/app/Platform/Console/OutboxDrainCommand.php:139). Do not put provider HTTP calls into those
 handlers. They materialize durable work; a separate operation worker performs external I/O. After-commit dispatch may
 reduce latency, but a durable scan/outbox repairs a crash before queue dispatch.

 platform-navdhya/app/Platform/Outbox/OutboxHandlers.php:28 currently replaces the previous handler when another
 consumer registers the same event type. Extend it to explicitly named consumers with duplicate-registration rejection;
  preserve existing notification consumers. Same-tenant database handlers may execute transactionally; external
 notifications and cross-database projections first create unique durable delivery rows. Bridge workers apply a
 destination event once, commit there, then acknowledge at the source; a crash between those steps safely replays.
 Never mark a cross-database projection delivered before the destination commit. This is an extension of the existing
 outbox, not a new message platform. Add a two-consumer regression proving Finance and notifications both receive a
 booking/payment event without overwriting each other.

 Provider events and money truth

 - Verify the raw-body signature against the correct registered provider account and test/live environment. Apply
 request-size/schema validation. Persist a durable receipt before acknowledging accepted events.
 - Deduplicate by provider event identity and corroborating immutable money-object identity, not status alone. Handle
 same event arriving concurrently, distinct events about one payment, and different payloads sharing an event ID.
 - Route through trusted mappings; a signed payload's arbitrary tenant note is not sufficient authority. Resolve
 ConnectionResolver from the tenant registry and clear context in finally, including workers.
 - Re-lock current payment/operation rows in processing transactions. Capture, refund and dispute facts persist even if
  Booking is terminal, temporarily unavailable, or no longer payable.
 - Wrong amount/currency/account, unknown order, refund-before-capture and duplicate capture are retained as
 unmatched/quarantined facts with reason, alert and replay—not silently discarded or acknowledged as successful booking
  payment.
 - Collection states are independent: UNPAID/PENDING/PARTIALLY_PAID/PAID/OVERPAID; refund progress is independent
 NONE/PENDING/PARTIAL/FULL; disputes are separate records. A failed attempt is not the final truth of an entire order.
 - Record all provider facts; request-time limits prevent over-refunds, but an externally observed excess is still
 recorded and investigated.
 - Reserve refundable funds while a refund is requested/pending/unknown. Count pending and completed refunds under the
 capture lock so two requests cannot each spend the same refundable balance.
 - Subscription, booking adjustment and initial-booking intents use different immutable source/purpose identities.
 Repeating the same action never invents a new identity to bypass a pending/unknown operation.
 - Disabling new payments, suspending a subscriber or taking a tenant offline must not stop signed financial-event
 ingestion, recovery, refunds or reconciliation. Domain notifications may defer, but money evidence survives.

 Payout and reconciliation controls

 Calculate eligible provider/vendor balances from obligations and verified money, subtracting approved
 fees/withholding/refunds/dispute holds. Display earned, payable, held, paid and reconciled separately. A compensation
 total is not a payout.

 Use maker/checker approvals, beneficiary verification, currency-specific thresholds, reason codes and a final
 pre-dispatch recheck of funds, holds and approval. Changed beneficiary/amount invalidates the approval. Support
 approved bank-statement-confirmed payouts where automated provider payout capability is unavailable; do not mark a
 manual request paid without settlement evidence.

 Reconcile internal captures/refunds/fees/payouts to provider statements and bank entries. Imports are idempotent;
 mismatches stay open. Recovery tools replay existing operations/events and record adjustments with approvals; they
 never offer an unrestricted “mark paid” button.

 8. Recurring tenant subscriptions and platform controls

 Subscription contract

 Start with fixed-price monthly/yearly plans, using existing central plans identifiers. Trials exist only when
 explicitly configured. Seat metering and usage billing are not included without a separate requirement; transaction
 commissions already belong to the transaction-finance flow.

 - Contract states: INCOMPLETE, TRIALING, ACTIVE, PAST_DUE, SUSPENDED, CANCELLED.
 - Initial collection/mandate acceptance activates the paid subscription; approved trials grant time-limited access
 without inventing a payment.
 - Unique billing cycles use the anchored [period_start, period_end) interval. Scheduler retries and provider
 redelivery cannot create another cycle/invoice for the same interval. Preserve original month-end/leap-year anchor,
 not accumulated date drift.
 - Separate scheduled calendar periods from the paid-through entitlement. Creating an invoice is not receiving payment;
  retrying it does not reset its due date or shift the billing anchor.
 - Do not schedule additional automatic cycle charges indefinitely while a prior cycle is unresolved. Apply the
 approved dunning/suspension policy; retained debt stays visible.
 - Provider recurring billing and an internal scheduler must not both charge the same cycle. Explicitly select one
 collection owner per subscription; provider-managed cycles are reconciled to local cycles.
 - Mandates store provider reference, consent terms/time/evidence and status. Revoked/expired/unsupported mandates
 block new automatic debits; never simulate off-session consent. Hosted renewal payment is an explicit alternative, not
  a hidden fallback.
 - Proposed default: upgrades effective after accepted prorated adjustment collection; downgrades and cancellation
 effective at paid period end. Immediate termination is a separate privileged action with previewed consequences. G4
 approval fixes production semantics.
 - Proration uses exact remaining/total interval proportions and the old/new immutable price versions; do not credit
 unpaid service. Split credit/charge tax correctly; no negative line hack. Plan-change retries reuse the same
 adjustment.
 - Dunning has approved retry intervals, attempt limit, grace, notifications and terminal action. Retry only when the
 prior operation is definitively resolved. Reactivation applies a documented paid-through date; it does not erase
 arrears.

 Entitlements and administration

 Preserve FeatureFlags' feature evaluation while adding a separately enforced subscription admission rule. Tenant
 overrides must not quietly bypass unpaid-account restrictions. Any temporary commercial exception is explicit,
 privileged, reasoned and expiring.

 Central entitlement projections are versioned/outbox-driven. Never mark the entire tenant deleted/inactive solely for
 SaaS nonpayment: preserve invoice access, payment recovery, existing-booking fulfillment, legally required records and
  refunds. Restrict new chargeable activity according to the approved grace policy.
 ┌──────────────────────┬────────────────────────────────────────────────────────────────────────────────────────┐
 │ Actor                │ Allowed actions                                                                        │
 ├──────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Customer             │ Own quotes/bookings, approved self-service changes, hosted payment, own                │
 │                      │ documents/refund tracking                                                              │
 ├──────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Partner/provider     │ Own offered/assigned work, accept/decline/start/complete, availability, own payout     │
 │                      │ statements; no arbitrary customer finance access                                       │
 ├──────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Booking operator     │ Authorized assignment/reschedule/cancellation/no-show decisions with reasons; cannot   │
 │                      │ declare money settled                                                                  │
 ├──────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Finance maker        │ Draft policy/data changes, credit/refund/payout requests, reconciliation proposals     │
 ├──────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Finance checker      │ Independently approve sensitive requests; cannot approve their own request or a        │
 │                      │ subsequently modified request                                                          │
 ├──────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Tenant billing admin │ Own tenant subscription/plan/mandate/documents; not platform tax policy or other       │
 │                      │ tenant ledgers                                                                         │
 ├──────────────────────┼────────────────────────────────────────────────────────────────────────────────────────┤
 │ Platform             │ Approved country/profile/policy publication, reconciliation and cross-tenant reporting │
 │ finance/compliance   │ through explicit scoped access                                                         │
 └──────────────────────┴────────────────────────────────────────────────────────────────────────────────────────┘
 Reuse PolicyEvaluationEngine, capability manifests, RequiresAcr, SodRuleSeeder, audit and tenant scope guards.
 Register fine-grained tax, document, refund, payout, reconciliation and billing capabilities. High-value approvals
 require fresh MFA; every export and override is audited. UI visibility is not authorization.

 9. API and application changes

 Contracts

 Use a new booking v2 contract for changed lifecycle/monetary semantics; extend legacy v1 additively during migration
 only. Both adapters call the same application services—no permanent dual engine. Determine v1 retirement from the
 verified consumer inventory, then remove it.

 Planned surfaces:
 - /api/v2/booking/reservations: create/list/detail/history, quote/checkout, cancel preview/submit, reschedule
 preview/submit, rebook and change-status reads.
 - Keep operation verbs consistent with existing conventions (:confirm, :cancel, :reschedule); every mutation requires
 observed version where applicable and idempotency key.
 - Assignment actions add explicit :start; accept/decline/complete/unassign use common guarded application commands.
 - /api/v1/payment/operations/{id} and refund/dispute status reads; sensitive refund/payout creation through
 capability-gated routes.
 - /api/v1/finance/documents, payouts, statements/reconciliation and authorized exports.
 - /api/v1/tax/...: registrations/classifications/data versions, dry-run comparison, independent publish approval and
 reporting.
 - /api/v1/billing/...: tenant subscription, plan-change preview/accept, renewal/mandate status and invoices; existing
 platform billing BFF becomes its scoped projection.

 Responses distinguish 401/403, ownership-safe 404, 409 version/state/idempotency conflict, 422 input/coverage errors,
 202 durable pending/unknown operations and dependency errors. A 202 operation remains visible and pollable. Return
 server-derived actions, version, expiry, tax breakdown, payment status and document links.

 User interfaces

 Implement the relevant UI alongside each backend slice, not as a final mock overlay:
 - Customer: selected pandit and actual service duration survive handoff; address/timezone capture; price/tax/terms
 review; checkout with pending/retry/expired states; separate service/payment/refund status; invoice/credit-note
 access; cancellation/reschedule previews and conflicts; history and rebook.
 - Partner: real accept/decline/start/complete, calendar conflict/reassignment notifications and distinct
 earned/held/payable/paid statements.
 - Web console/dashboard: consistent server-backed booking actions, finance approval queues, jurisdiction/configuration
  review, reconciliations, payout controls and SaaS billing management.
 - Preserve BFF auth/cookies/DPoP/tenant forwarding. Forward the user's observed version; do not fetch a newer version
 to make an old form appear current. Maintain an idempotency key across renders, network retries and refresh recovery.
 - Replace misleading “Confirm booking” draft-only completion with accurate step/status labels. Existing demo booking
 forms in apps/web/app/book/client.tsx and apps/web/app/pandits/book/client.tsx must hand off to the real customer flow
  rather than log/alert success.
 - Use accessible labels, keyboard actions, focus/error summaries, currency/timezone formatting and the locked brand
 palette; no green in new core identity UI. Do not redesign unrelated screens.

 10. Ordered implementation phases

 No new flow is enabled in production until its financial and jurisdictional dependencies are complete. Local read-only
  analysis has not established a green test baseline.
 ┌─────────────────────┬─────────────────────────────────────────────────────┬─────────────────────────────────────┐
 │ Phase               │ Work and dependencies                               │ Exit acceptance                     │
 ├─────────────────────┼─────────────────────────────────────────────────────┼─────────────────────────────────────┤
 │ P0 — Baseline and   │ Inventory all booking/billing consumers, existing   │ Reproducible baseline; known        │
 │ executable          │ records/provider accounts, commands and installed   │ failures separated from             │
 │ contracts           │ tooling. Capture test baseline. Finalize schemas,   │ regressions; no mock/target spec    │
 │                     │ transition fixtures, posting examples and G1–G5     │ represented as production           │
 │                     │ ownership.                                          │ functionality.                      │
 ├─────────────────────┼─────────────────────────────────────────────────────┼─────────────────────────────────────┤
 │ P1 — Integrity and  │ Add forward migrations/shared-dedicated upgrade     │ A1–A4, A21–A22; shared/dedicated    │
 │ tenant foundation   │ path, command dedupe/version contracts, resolved    │ rollback tests; existing            │
 │                     │ Payment connection and routing/inbox foundation.    │ provider-order durability behavior  │
 │                     │ Fix current hash/race/old-intent-total defects with │ preserved.                          │
 │                     │ regressions.                                        │                                     │
 ├─────────────────────┼─────────────────────────────────────────────────────┼─────────────────────────────────────┤
 │ P2 —                │ Extract Booking use cases;                          │ A5–A9; simultaneous resource        │
 │ Booking/resources   │ revisions/addresses/provider selection,             │ contention cannot oversell; no      │
 │                     │ holds/capacity/stock, explicit fulfillment, expiry, │ implicit start or deleted           │
 │                     │ reviewed change proposals. Depends P1.              │ assignment history. Financial       │
 │                     │                                                     │ change application waits for P4–P5. │
 ├─────────────────────┼─────────────────────────────────────────────────────┼─────────────────────────────────────┤
 │ P3 — Commercial     │ Seller/issuer/collector profiles, pricing           │ A10–A13; all country datasets have  │
 │ quotes and Tax      │ composition, tax snapshots and approved-data        │ explicit coverage, effective        │
 │                     │ control UI; IN/GB/AE and nationwide US packages.    │ versions and evidence;              │
 │                     │ Depends P1; integrates P2 revisions.                │ invalid/unknown coverage blocks     │
 │                     │                                                     │ checkout.                           │
 ├─────────────────────┼─────────────────────────────────────────────────────┼─────────────────────────────────────┤
 │ P4 — Payment        │ Prepare/register/dispatch/recover, hosted checkout, │ A14–A18; crash tests at each        │
 │ operations          │ durable event dedupe, captures/refunds/disputes and │ boundary; no blind duplicate        │
 │                     │ late-payment handling. Depends P1/P3 and booking    │ provider operation or discarded     │
 │                     │ integration from P2.                                │ money fact.                         │
 ├─────────────────────┼─────────────────────────────────────────────────────┼─────────────────────────────────────┤
 │ P5 — Finance,       │ Documents/sequences/postings,                       │ A19–A24; customer quote-to-bank     │
 │ adjustments and     │ cancellation/reschedule monetary execution, partial │ trace reconciles; immutable         │
 │ settlement          │ refunds, AP/payouts, reconciliation and finance     │ books/documents; approved           │
 │                     │ controls. Depends P2–P4.                            │ payout/refund controls enforced.    │
 │                     │                                                     │ Transaction release cannot precede  │
 │                     │                                                     │ this gate.                          │
 ├─────────────────────┼─────────────────────────────────────────────────────┼─────────────────────────────────────┤
 │ P6 — Tenant         │ Platform billing context, plan prices,              │ A25–A28; one obligation per period, │
 │ subscriptions       │ cycles/mandates/proration/dunning, SaaS             │ no double collection owner, no      │
 │                     │ tax/invoices, central entitlement projection and    │ false paid entitlements,            │
 │                     │ tenant console. Depends P3–P5.                      │ recoverable renewal failure.        │
 ├─────────────────────┼─────────────────────────────────────────────────────┼─────────────────────────────────────┤
 │ P7 — Cutover and    │ Finish all consumer cutovers, E2E acceptance,       │ A29–A32 and full verification       │
 │ system verification │ production-sized index/migration rehearsal,         │ suite; staged activation only after │
 │                     │ recovery/ops evidence. CI activation proposal       │ country/model/provider gates pass.  │
 │                     │ handled separately below.                           │                                     │
 └─────────────────────┴─────────────────────────────────────────────────────┴─────────────────────────────────────┘
 Each phase delivers migrations, code, API contracts, role checks, tests and relevant UI together. Do not claim a phase
  complete for interfaces, empty endpoints or skipped test lanes.

 11. Acceptance criteria and tests

 Extend the existing suites before adding narrowly named suites. Tests assert exact status/error codes, persisted
 financial/resource effects, audit/outbox identity and absence of duplicate effects—not only response success.
 ┌─────┬───────────────────────────────────────────────────────────────────┬───────────────────────────────────────┐
 │ ID  │ Given / when / expected outcome                                   │ Test layer                            │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A1  │ Same create key and canonical payload, including concurrent       │ BookingIdempotencyTest; real MySQL    │
 │     │ requests → one booking/result; different address/provider/target  │ concurrency                           │
 │     │ with that key → 409 in normal and race paths                      │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A2  │ User submits version N after any competing mutation → 409 and no  │ BookingReservationTest; customer      │
 │     │ overwritten schedule/status/resources; BFF submits N, not a       │ BFF/component                         │
 │     │ refreshed version                                                 │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A3  │ Any booking/financial mutation fails during transition, audit or  │ PHPUnit fault injection, shared +     │
 │     │ outbox write → all same-connection changes roll back              │ dedicated                             │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A4  │ Provider intent survives a failed booking update; price changes   │ Extend PaymentOrderCommitTest,        │
 │     │ before retry → original intent/quote/booking amount remains       │ PaymentCheckoutTest                   │
 │     │ consistent and no second payable order                            │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A5  │ Competing customers book the same provider/temple/last kit,       │ MySQL multi-connection/process        │
 │     │ including initially empty calendars → capacity respected and only │ barrier tests                         │
 │     │ valid allocations commit                                          │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A6  │ Exhaustive state/actor/command matrix → every declared transition │ Booking domain table-driven tests     │
 │     │ succeeds only with guards; every undeclared one rejects; refund   │                                       │
 │     │ never turns completed service into cancelled service              │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A7  │ Missing/ineligible provider, wrong-owner address, invalid         │ Booking/availability integration + UI │
 │     │ variant/duration, DST gap/ambiguity, adjacent intervals/buffer    │                                       │
 │     │ conflicts → precise validation and no partial reservation         │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A8  │ Expiry competes with capture/cancellation/assignment → one lawful │ Clock-controlled integration + MySQL  │
 │     │ allocation outcome; late money persists but expired booking does  │ race                                  │
 │     │ not resurrect                                                     │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A9  │ Explicit start/complete; provider declines/cancels;               │ Extend BookingFulfillmentTest,        │
 │     │ reassignment/no-show/rebook → actor/compensation/history          │ lifecycle events                      │
 │     │ preserved and notifications deduplicated                          │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A10 │ Same frozen quote input/rule/dataset versions → identical         │ Tax/price golden and bounded          │
 │     │ canonical calculation/hash; sum of lines/components equals total  │ generated cases                       │
 │     │ with no float/overflow                                            │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A11 │ Country fixture matrix includes service/kit/platform fee/SaaS,    │ New Tax PHPUnit suites; independent   │
 │     │ applicable exemption/reverse charge/inclusive tax, rate           │ approved expected values              │
 │     │ boundaries, registration/seller model changes                     │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A12 │ US dataset → coverage report includes 50 states + DC and          │ Tax                                   │
 │     │ applicable local districts; ambiguous/stale/unreviewed            │ import/coverage/address-resolution    │
 │     │ address/rule fails closed; explicitly reviewed non-taxability     │ integration                           │
 │     │ remains valid                                                     │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A13 │ Published price/tax/seller-policy update → accepted old           │ Tax/commercial integration + console  │
 │     │ quote/document unchanged; expired quote needs renewed consent;    │                                       │
 │     │ self-approval or overlapping rates rejected                       │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A14 │ Duplicate/concurrent/out-of-order webhook, including failure then │ Extend PaymentWebhookTest; consumer   │
 │     │ capture and refund before capture → durable money facts once,     │ transaction tests                     │
 │     │ correct eventual totals and replayable unmatched records          │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A15 │ Invalid signature/account/environment, mismatched                 │ Provider contract + security          │
 │     │ amount/currency, unknown order → no unauthorized                  │ integration                           │
 │     │ allocation/booking confirmation; valid anomalous facts retained   │                                       │
 │     │ for review                                                        │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A16 │ Crash before call, after provider acceptance, before outcome      │ Fake provider with deterministic      │
 │     │ save, before booking apply, after consumer commit/before          │ fault boundaries; MySQL               │
 │     │ acknowledgement → eventual recovery or visible UNKNOWN, never     │                                       │
 │     │ blind re-creation                                                 │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A17 │ Concurrent partial refunds plus pending/unknown refund → internal │ Payment/Finance race tests            │
 │     │ requests cannot exceed eligible captured balance; external excess │                                       │
 │     │ still recorded as incident                                        │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A18 │ Cancelled/expired booking, failed assignment or suspended tenant  │ Lifecycle/payment integration         │
 │     │ receives capture/refund/dispute → money recorded without invalid  │                                       │
 │     │ state change; recovery continues                                  │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A19 │ Concurrent invoice issuance/source replay → one document/posting; │ Finance MySQL + document tests        │
 │     │ unique immutable fiscal number; draft produces no posting;        │                                       │
 │     │ approved correction links original                                │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A20 │ Partial credits, retained cancellation fee and commissions →      │ Approved posting golden fixtures      │
 │     │ original taxable basis/components reversed correctly,             │                                       │
 │     │ deterministic final cent, principal/agent revenues not confused   │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A21 │ Two tenants share provider account; one promoted to dedicated DB; │ Tenant isolation/promotion matrix     │
 │     │ workers alternate contexts → correct owner/connection and no      │                                       │
 │     │ leakage through reads, exports, jobs or provider routing          │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A22 │ Upgrade shared and already-populated dedicated schemas,           │ Migration rehearsal tests             │
 │     │ interrupt/restart backfill → same counts/amounts/hashes and no    │                                       │
 │     │ rerun provider effects; uncertain legacy rows visibly quarantined │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A23 │ Payout maker approves own request, beneficiary changes after      │ Authz/SoD + payout integration        │
 │     │ approval, hold appears before dispatch, duplicate result arrives  │                                       │
 │     │ → reject/invalidate/recheck without excess payout                 │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A24 │ Duplicate statements, mismatched gross/fee/net, currency          │ Reconciliation/ledger integration     │
 │     │ mismatch, chargeback and closed-period correction → balanced      │                                       │
 │     │ records or explicit unresolved case; no silent write-off          │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A25 │ Two renewal schedulers plus repeated provider events at           │ Billing clock/concurrency tests       │
 │     │ month-end/leap-year boundary → one cycle/invoice/economic         │                                       │
 │     │ collection; original anchor and paid-through preserved            │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A26 │ Failed/unknown renewal, exhausted dunning, revoked mandate,       │ Billing/provider integration + UI     │
 │     │ absent provider capability → no unauthorized repeated debit;      │                                       │
 │     │ recovery/past-due/suspension correctly shown                      │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A27 │ Upgrade/downgrade/cancel/reinstate retries with proration → exact │ Billing/Finance golden + boundary     │
 │     │ old/new price allocation, no credit for unpaid periods, no        │ tests                                 │
 │     │ renewal after effective cancellation                              │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A28 │ Stale/reordered entitlement messages, tenant overrides, grace     │ Billing/FeatureFlags/authorization    │
 │     │ expiry → newer subscription wins; only approved temporary         │ integration                           │
 │     │ exception bypasses admission; existing service/refunds remain     │                                       │
 │     │ accessible                                                        │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A29 │ Real customer journey: discover → provider/address/time →         │ Full-stack Playwright, fake/test      │
 │     │ quote/taxes → checkout → verified capture → assignment → start →  │ provider                              │
 │     │ complete → invoice/settlement; reloads/retries preserve state     │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A30 │ Real browser cancellation/partial refund; reschedule with         │ Full-stack Playwright, multiple       │
 │     │ increase/decrease and expired replacement hold; SaaS              │ authenticated roles                   │
 │     │ signup/renewal failure/plan change; operator maker/checker        │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A31 │ Mobile/keyboard/error/loading/offline-resume paths; foreign       │ Browser checks +                      │
 │     │ booking/document access; no stale console state or browser        │ component/accessibility tests         │
 │     │ console/network regressions                                       │                                       │
 ├─────┼───────────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ A32 │ Build/typecheck/lint/contracts/boundaries pass; no real           │ Full verification and test-discovery  │
 │     │ messages/charges; all new acceptance specs actually selected by   │ check                                 │
 │     │ runners                                                           │                                       │
 └─────┴───────────────────────────────────────────────────────────────────┴───────────────────────────────────────┘
 The real MySQL lane must use committed fixtures and independent connections/processes for races. SQLite and a single
 PHPUnit rollback transaction cannot establish row-lock/concurrency correctness. Use synchronization barriers rather
 than arbitrary sleeps; repeat race schedules and assert database invariants.

 Verification commands and safety

 Resolve installed tooling first: repository declares Node 20/pnpm 9.15.4; the current session reports Node 26. Do not
 silently change engines or install packages to mask incompatibility. Inspect installed Turbo docs/README.md and
 relevant bundled pages before Turbo command/config work, per AGENTS.md.

 Existing commands to run after implementation, from the indicated working directory:
  # platform-navdhya — isolated local test databases only
  php artisan test
  php artisan test --configuration=phpunit.mysql.xml
  ./vendor/bin/pint --test
  ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
  ./vendor/bin/deptrac analyse --no-cache
  php artisan api:compose --check
  php artisan platform:doctor --json

  # repository root — explicit root suites are not assumed to run via app scripts
  pnpm exec vitest run tests/customer tests/partner tests/payment tests/pricing tests/finance tests/api
  tests/architecture
  pnpm --filter @navdhya/customer test
  pnpm --filter @navdhya/partner-app test
  pnpm --filter @navdhya/dashboard test
  pnpm --filter @navdhya/web test
  pnpm verify
  pnpm build
 For UI acceptance, load the existing run skill, start the actual Laravel/customer/partner/web stack and use
 Chrome/Playwright. Extend the existing guarded E2E runner and testMatch to include booking/billing tests; verify
 collection with Playwright's test listing before trusting results. Capture traces/screenshots for failing flows.
 Preserve existing auth and console smoke tests.

 No live Razorpay/MSG91, raw production data, .env edits, shared-database resets or manually invoked migrate:fresh.
 Test migrations only in verified disposable databases. If any UI/provider/MySQL lane cannot be executed, report it as
 unverified; do not label acceptance complete.

 12. Migration, release and operations

 1. Inventory current tenant connections, booking statuses, amounts/currencies, provider references, assignments and
 active customer contracts. Save counts and currency-separated monetary control totals.
 2. Establish an executable forward-only tenant upgrade mechanism: explicit connection, version/checksum journal,
 dry-run/check mode, tenant lock/checkpoint, schema readiness and resumable backfill. Existing schema cloning is
 provisioning only, not an upgrade mechanism. New tenant migrations must target the passed connection; leave
 already-applied historical migrations unchanged.
 3. Expand schemas first. Backfill source identities, provider-account routing and revised data with deterministic
 mappings. Never invent missing historical tax, seller, consent, address or financial documents.
 4. Migrate dedicated tenants' payment rows from shared ownership only after reconciling duplicates and provider
 references. Pause the affected write/dispatch paths during each cutover, keep ingesting durable provider receipts,
 switch routing, verify totals, then replay receipts.
 5. Legacy PAYMENT_CONFIRMED/REFUNDED states require transition/payment evidence to recover operational state. If
 evidence is insufficient, retain an explicit legacy-review record and prevent unsafe automatic actions rather than
 guessing.
 6. Reconcile against provider records for captures/refunds the old handler may have ignored. Backfill through
 idempotent domain operations with provenance; never directly mark uncertain bookings paid.
 7. Update all API/BFF/UI consumers, compare read projections, then cut over to the single new write path. Remove
 obsolete status derivation/adapters only after verified usage; no indefinite compatibility layer or
 double-authoritative writes.
 8. Activate by approved tenant/model/jurisdiction/provider using existing admission/configuration controls. Disabling
 new sales never disables financial evidence/recovery. Approval of this plan is not authorization for live migrations,
 real charges, deployments or publishing.
 9. Recovery is forward repair: disable new initiation, continue event ingestion, inspect unknown/unmatched operations,
  repair/replay by stable IDs. Never roll back away already-issued documents or provider money. Rehearse backup/restore
  and post-restore reconciliation because providers continue independently of database backups.

 Operational evidence and alerts:
 - Oldest unknown payment/refund/payout operation, unmatched provider receipts, exhausted jobs/outbox lag.
 - Expired-but-held resources, over-capacity/stock invariants, assignment/change requests beyond deadline.
 - Unbalanced journal rejection, unexplained provider-clearing balance, settlement mismatches, duplicate document
 number rejection.
 - Tax dataset coverage/staleness and rejected jurisdiction determinations; policy/version publication history.
 - Renewal backlog, duplicate-cycle prevention, overdue entitlement projection and mandate revocation.
 - Correlation: booking/change/quote → intent/operation/provider IDs → document/journal → payout/statement; redacted
 logs, protected evidence and jurisdiction-specific retention.

 Choose numeric performance/alert thresholds from P0 workload and deployment measurements, not invented SLAs. Use
 representative indexed data and query plans to prove bounded list/availability/reconciliation behavior before release.

 CI approval boundary

 A separate, explicit approval is required before modifying shared CI/CD. Proposed change: root workflow invokes
 backend PHPUnit/static/API checks, includes root API contracts and the new acceptance suites, and runs MySQL
 concurrency/E2E in a correctly provisioned lane. Do not infer protection from the dormant nested workflow, silently
 weaken gates, change repository permissions or enable live external services.

 13. Critical implementation file map

 Existing files to refactor/extend:
 - platform-navdhya/app/Modules/Booking/{Domain/BookingLifecycle.php,Http/Controllers/ReservationController.php,Booking
 PaymentTransitions.php,BookingFulfillmentTransitions.php,Models/Booking.php,ModuleServiceProvider.php,Routes/api.php}
 - platform-navdhya/app/Modules/Payment/{PaymentIntents.php,Models/PaymentIntent.php,Gateways/PaymentGateway.php,Gatewa
 ys/RazorpayGateway.php,Gateways/FakeGateway.php,Http/Controllers/PaymentWebhookController.php,ModuleServiceProvider.ph
 p,Routes/api.php}
 - platform-navdhya/app/Modules/Catalog/QuoteCalculator.php and Catalog contracts/pricing configuration.
 - platform-navdhya/app/Modules/Pandit/PanditAssignmentManager.php, PanditAvailabilityManager.php; Temple capacity and
 Vendor inventory/payout contracts/models.
 - platform-navdhya/app/Platform/Tenancy/{ConnectionResolver.php,Console/TenantProvisionSchemaCommand.php,TenantPromoti
 on.php} and affected tenant migration/test paths.
 - platform-navdhya/app/Platform/{Outbox,Audit,Features,Authz}, platform-navdhya/routes/console.php,
 platform-navdhya/routes/platform/api.php,
 platform-navdhya/app/Http/Controllers/Ops/{BillingController.php,BookingsController.php,PayoutsController.php}.
 - apps/customer/application/bookings/{confirm-booking-draft.ts,laravel-booking-mappers.ts},
 apps/customer/app/(protected)/book/start/page.tsx, apps/customer/app/api/bookings,
 apps/customer/adapters/backend/customer-api-client.ts, apps/customer/presentation/bookings/BookingDetailPage.tsx.
 - apps/partner/presentation/pandit/PanditAssignmentsView.tsx, partner temple/vendor views,
 apps/partner/adapters/backend/partner-api-client.ts.
 - apps/web/components/console/{admin/BookingsPanel.tsx,admin/PayoutsPanel.tsx,platform/BillingPanel.tsx},
 corresponding BFF routes, public booking handoff and affected legal pages.
 - apps/dashboard/app/(app)/bookings/page.tsx, apps/dashboard/api/client.ts; shared API contracts and booking
 presentation types.
 - platform-navdhya/tests/Modules/{Booking,Payment,Pandit,Temple,Vendor}, tenant isolation/promotion tests;
 tests/{customer,partner,payment,pricing,finance,api,architecture}; console component tests; e2e/playwright.config.ts
 and guarded runner.

 Necessary new code is confined to Booking/Payment use cases and persistence, Tax/Finance/Billing modules and their
 contracts/tests, versioned schema upgrades, and missing feature-specific UI/API routes. Do not create replacement
 frameworks or unrelated documentation trees.

 Documentation checked for implementation semantics

 Laravel 12 documentation confirms retried transaction closures, row locks inside transactions and after-commit job
   └ Read 54 lines

 ▪ The scope now includes India, the UK, the UAE, and all 50 US states plus DC, along with transaction and subscription
   billing. I’m organizing the work into dependency-ordered phases, each with acceptance tests, migration checks, and
   explicit handling for payment failures and uncertain provider responses.

 Thinking
 │ Verifying plan logic
 │ I need to evaluate a complex plan that’s around 11,000 words. The aim is to create content of approximately 13,000
 │ … +14 rows (Ctrl+O)
 │ transaction handlers register one handler per event. I need to verify if the existing outbox dispatcher can only
 │ notify a single handler per event, and that event fanout needs to be deterministic. I'll look into the known
 │ OutboxHandlers file for more information.
 ▪ Read(C:\Users\...\OutboxHandlers.php)