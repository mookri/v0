# Trust, Safety & Compliance — Onboarding Module Specification v2

**Module:** `app/Modules/Onboarding` (+ a new `app/Domain/Verification` claims kernel — §3)
**Status:** Draft — for review. **§1.2 contains findings that mean the platform's current
"verified" state is not evidence-backed. This is the most consequential document of the three
calendar/search/trust specs, because the failure mode here is approving a person to conduct
religious rites and receive institutional money on the strength of a dropdown.**
**Slices:** S105 → S124 (Search v3 = S65–S84, Calendar v3 = S85–S104)
**Extends:** ADR-0020 (auth federation), ADR-0021 (auth code hardening), ADR-0023 (fail-closed revocation)
**Related:** [`docs/search/SPEC.md`](../search/SPEC.md) (the `verified` flag is a search *ranking*
input), [`docs/seo-content/SPEC.md`](../seo-content/SPEC.md) (identity attributes feed Panchang-based
personalization)
**Methodology:** every task in §18 follows [`docs/specs/README.md`](../specs/README.md) §2 template,
§3 AC shapes, §4 Definition of Done.

---

## 0. How to read this document

```text
Epic       E-TR-<nn>           e.g. E-TR-02
Task       T-TR<epic>-<nn>     e.g. T-TR02-03
AC         AC-T-TR02-03-<n>
Defect     T-<nn>              verified defect in shipped trust code (§1.2)
Claim      verification claim  (§4): identity | qualification | institutional_authority | …
Mode       native | manual | official | financial   (§2.3 evidence tiers)
```

Normative words: **MUST** = blocker, **SHOULD** = justified deviation in the PR, **MAY** = optional.

§1 is facts verified against this working tree on 2026-11-10, most by direct `file:line` reads and
`grep` sweeps; where a claim could not be verified it is labelled **(unverified)** and is not used
as a requirement. §2.4 states the legal-posture questions this document deliberately does **not**
answer. Everything after §2 is a proposal.

A note on tone. Verification in India runs through real institutions with real asymmetries: a
pandit's certificate may be genuine from an institution that has no website, a temple may be
administered by a state endowment board, and a small vendor may be lawfully outside GST entirely.
Nothing here is designed to make the platform a gatekeeper of religious legitimacy. The design goal
is narrower and defensible: **each claim the platform makes about a partner must have a recorded
basis, a named reviewer, a scope, and an expiry — and where no authoritative source exists, the
platform says "manually reviewed" instead of "verified."**

---

## 1. Evidence baseline

### 1.1 What actually ships

`Onboarding` is a genuine, reasonably-built module — 1,584 LOC / 21 files, not a stub:

| Concern | Shipped | Verified at |
| --- | --- | --- |
| Application | ULID, `tenant_id`, `identity_id`, `track`, `track_version`, `status`, `market`, `supersedes`, `reviewer_id`, `rejection_code` | `Database/Migrations/2026_10_01_000001_…php:18-35` on `tenant_shared` |
| States | `DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED / REJECTED`, + `APPEALED`, `WITHDRAWN` | `Models/PartnerApplication.php:37-49` |
| Draft evidence | per-step JSON on the application, **64 KB hard cap per application** | `DraftController.php:75-87` |
| Documents | `application_documents` — `kind`, `status`, `object_key`, `sha256`, `reason_code` | migration `…000002…:20-30` |
| Upload validation | 10 MB max, `jpeg\|png\|pdf` whitelist, sha256 computed, content-addressed key | `DraftController.php:130-145` |
| Step validators | shape/format per step | `StepValidators.php:16-148` |
| Submit challenge | OTP via shared `Platform/Identity/Otp/OtpChallengeManager`: attempt caps, compounding resend 60/180/540 s, rolling hourly cap, supersession, single-use delete-on-match, codes hashed | `SubmitController.php:104-293`, `OtpChallengeManager.php:83-115,172-183,230,272` |
| Review records | append-only table | migration `…000004…:15-26` |
| Kernel audit | **per-tenant `prev_hash → sha256` hash chain, `lockForUpdate`, same-transaction write** | `Platform/Audit/AuditWriter.php:32-83`, `database/migrations/2026_08_28_000003_create_platform_audit_logs_table.php:16-28` |
| Review gating | capabilities `onboarding:review:{view,approve,reject}`, **`stepup:2`** on decisions | `Routes/api.php`, `ReviewController.php:111-183` |
| Manifest | 6 permissions (approve/reject `sensitive`), 2 scopes, 3 events, OpenAPI fragment | `module.json` |
| Tests | 8 files / 25 tests, incl. a real step-up + audit-row + countersign assertion | `tests/Modules/Onboarding/` (e.g. `OnboardingReviewTest.php:106-145`) |

**The audit architecture is the best thing in this repo.** A hash-chained, same-transaction,
actor-stamped audit writer already exists and Onboarding decisions already flow through it
(`ReviewController.php:167-172`). This spec *builds on* that rather than inventing a parallel one —
and §1.2 will show the one place where the pattern was half-applied.

### 1.2 Defect register

**T-01 — `verification_status` is a dropdown, and it gates money and rituals.**
Three writers set the verified state by direct assignment from an admin HTTP verb, with **no link to
any application, document, review record or claim**:
`Pandit/Http/Controllers/PanditProfileController.php:247`,
`Temple/…/TempleManagementService.php:129-132`, `Vendor/…/VendorManagementService.php:60`
(all `forceFill(...VERIFIED)`). Enums are labels only — `PanditProfile.php:63-71`
(`PENDING / DOCS_SUBMITTED / INTERVIEW_SCHEDULED / VERIFIED / REJECTED`), `Temple.php:56-58`,
`Vendor.php:42-43`.
Reads that *depend* on it: public directory (`PanditProfileQueries.php:35,165`), availability
(`PanditAvailabilityManager.php:100`, `PanditAvailabilityController.php:55`), **booking eligibility**
(`PanditSchedulePolicyService.php:61`, `TempleSchedulePolicyService.php:38`, and a hard gate at
`PanditProfileController.php:65`), temple public pages (`TempleProfileQueries.php:32`,
`TemplePublicController.php:63`), **search ranking** (`PanditSearchProvider.php:54`,
`TempleSearchProvider.php:47` → `card_json.verified`), and `InstantSearchController.php:141`.
So a single unverified click can (a) publish a pandit to bookings, (b) raise their search rank, and
(c) surface a "verified" card — with no recorded evidence, scope, or reviewer chain behind any of it.
This is the brief's central warning, "one claim must never substitute for another," inverted: today
one field substitutes for all of them.

**T-02 — Customer-facing "Verified" is hardcoded marketing.**
`apps/web/components/sections/QuickActions/TrustBadges.tsx:16` renders the literal string
`Verified<br />Pandits` — static copy, not derived from any partner's state; likewise `hero.tsx` and
`platform-navdhya/resources/views/tables/basic.blade.php:2938`. The admin panels
(`PanditsPanel.tsx:44`, `VendorsPanel.tsx:45`) do render the raw enum through a `StatusBadge`.
Net effect: the trust promise shown to a customer is disconnected from the trust state stored for the
partner, which is itself disconnected from evidence. **Any badge that cannot be traced to a claim row
is a product lie.**

**T-03 — Documents are never actually verified.**
`ApplicationDocument::STATUS_VERIFIED` / `STATUS_REJECTED` (`Models/ApplicationDocument.php:30-32`)
have **zero writers**. Only `STATUS_PENDING` is ever set (`DraftController.php:145`), and
`reason_code` is never populated by anything. So the table designed to hold per-document outcomes
holds a uniform `PENDING` forever. The `packages/partner/src/onboarding/documents.ts:38-51` state
machine that models these transitions exists and is **unused**.

**T-04 — Recorded integrity is never checked, and the fix already exists elsewhere in-repo.**
`sha256` is stored per document (`application_documents.sha256`) and **never re-verified** — no
command, no reviewer action, no job. Meanwhile `Platform/Identity/Federation/Console/VerifyAuthEventChainCommand.php:11,56-57`
already verifies a hash chain and exits non-zero naming the break for auth events. The general
`audit_logs` chain written by `AuditWriter` has **no equivalent verifier**
(`AuditLogController` exposes only `index()`). This is not a missing capability; it is an
un-finished one with a working precedent two directories away.

**T-05 — Reviewers cannot open the evidence they are approving.**
There is **no document retrieval path anywhere in the application**: `Storage::download`,
`temporaryUrl`, `createSignedUrl` and `response()->download` have zero hits in `app/` (verified by
grep). `config/filesystems.php:16` defaults to the `local` disk at `storage_path('app')` (`:35`) —
good, that is private storage — but there is no `storage:link` and no signed-URL endpoint, so the
bytes are unreachable by design. **Consequence: the entire review workflow, as shipped, can only
approve on the strength of a filename and a row count.** This single gap explains why T-01/T-03
behaviour looks the way it does: there was never a working path to look at a document.

**T-06 — Dual control is inverted and, where present, fake.**
`ReviewController.php:86` — approve calls `decide(..., null, null)`: **no second reviewer at all.**
`:89-105` — reject requires `second_reviewer` as a `required|string` **client-supplied value**,
checks only that it differs from the caller (`:97`), never authenticates that it names a real user,
holds the `onboarding:review:approve` capability, or consented. It is then stored as a note string
(`:163`). So the control is present for the *less* dangerous action (rejecting someone) and absent
for the *more* dangerous one (approving), and in both cases it is a self-declared string rather than
a verified second signature. This is exactly the brief's "do not allow the same reviewer to initiate
and finally approve" requirement, implemented as theatre.

**T-07 — Reject is broken from the admin UI.**
`apps/web/components/console/admin/ReviewActions.tsx:29` posts `second_reviewer: ''`, which fails
`['required','string']`. Rejection therefore returns 422 from the only interface that performs it.
**Third occurrence of one bug class across three modules** — after Search **D-11** (analytics +
trending routes 404) and Calendar **P-10** (six advertised calculators 404). The pattern is not
"some routes are missing"; it is that **no test traverses the UI→API seam**, so a payload or route
mismatch is invisible until a user hits it. §18 therefore puts a seam test in every epic, and §20
R-01 makes the class itself the risk.

**T-08 — No segregation of duties.**
Nothing prevents an identity holding `onboarding:review:approve` from approving an application whose
`identity_id` is their own. No maker-checker invariant exists in code or data.

**T-09 — GSTIN "validation" is a length check.**
`StepValidators.php:107-110`: `preg_match('/^[0-9A-Z]{15}$/', $gstin)`. The brief anticipated this
would be enough to check format *and* checksum; reality is weaker than assumed. There is no
two-digit state-code validity check, no validation that characters 3–12 form a well-formed PAN, no
check-digit computation at position 15, no entity-type digit at position 13, and **no authoritative
source lookup anywhere** (grep: no GST API, no GSP client, no portal call). The only other
references to GSTIN in the repo are presence checks
(`packages/partner/src/onboarding/steps.ts:27-28`, `tracks.ts:39`). Note the brief is right that a
vendor lawfully outside GST must not be rejected — today the form *requires* 15 characters
unconditionally (`:108-110`), so a legitimately unregistered small vendor is blocked from applying at
all. **That is a hard availability bug, not a compliance nicety.**

**T-10 — GSTIN and bank account number are stored in an untyped plaintext JSON blob.**
`DraftController.php:75-87` writes each step's raw value into `partner_applications.evidence`
(the `json` column added at migration `…000002…:12-13`) via `forceFill(...)->save()`. The `business`
step carries `gstin` and `bankAccount` (`StepValidators.php:107-114`). So financial identifiers land
in an uncast, unencrypted, schemaless blob with no field-level typing, no PII classification, no
column-level access control, and no masking path. Supporting facts: `defuse/php-encryption` appears
in `vendor/` **only as a transitive dependency of `laravel/passport`** (absent from
`composer.json` `require`); direct `Crypt::` use is confined to four unrelated sites
(`MfaService.php:42,282`, `RefreshIdempotencyStore.php:66,94`, `ConnectionResolver.php:97`,
`ResolvesOAuthTokens.php:75`). Also `Tax/Database/Migrations/2026_11_08_000005_…php:18` stores
`registration_number` as plaintext `varchar(64)`.

**T-11 — There is nowhere to record a bank verification result.**
No `bank_account`, `ifsc`, `account_holder` or payout-ownership column exists in any onboarding or
vendor table (verified: the only bank value is the free-text JSON in T-10). `Vendor` has no
`REJECTED` state (`Vendor.php:42-43`) — so a failed payout check has no representable outcome. The
brief's penny-drop pipeline therefore has **no destination schema**, which is why §6 designs it
before any provider is wired.

**T-12 — A declared risk control that enforces nothing.**
`Platform/Identity/Otp/OtpRiskTier.php:13` defines a tier **T3 "vendor onboarding-kyc" with a 2-of-2
quorum**, and `:17-18` states the quorum **is not enforced** — the tier is plumbed as an enum and
changes no behaviour. A reader auditing the code would reasonably conclude vendor KYC requires two
approvers. It does not. This is the **same shape as Search D-01** (a 0.45 ranking weight on
all-zero columns), **Calendar P-08** ("verified through 2028" with no verification) and **T-06**.

**Meta-finding — the recurring integrity failure in this codebase is *declaration without
enforcement*, not absence.** Across three modules: weights referencing dead columns, a corpus range
labelled verified, a KYC quorum that is never checked, document statuses nothing writes, a second
reviewer field nothing authenticates, and a `sha256` nothing re-verifies. Each is individually
minor. Together they mean **the codebase's stated controls cannot be trusted at face value, and
neither can a spec that cites them.** The design response, applied throughout §18–§19: every
control in this module gets a **falsifiable test that fails when the enforcement is absent** — not a
column, not an enum, not a docblock. Where a control cannot be enforced yet, it must not be declared.

**T-13 — OTP proves possession, not identity — and must not be described otherwise.**
The submit challenge (`SubmitController.php:104-293`) is well engineered for what it does: it
establishes control of a phone number at submission time. It does **not** verify that the person is
who their documents claim. `OtpChallengeManager.php:115` hashes codes, `:272` deletes on match,
replay is bound to the `application_id` payload (`SubmitController.php:277-282`) — all correct.
Nothing in §4/§7 may inherit a "verified" implication from it.

**T-14 — `verify()` side effects that publish entities.**
`TempleManagementService.php:129-132` sets `verification_status = VERIFIED` **and silently flips
`status = ACTIVE`**. One admin action therefore both asserts a trust claim and publishes a temple to
the marketplace, with the two concerns conflated in a single transaction. `VendorManagementService::verify`
writes **no audit row at all** (T-15), and `PanditProfileController.php:247-254` does — so three
superficially identical admin actions have three different governance profiles.

**T-15 — Uneven audit coverage for sensitive actions.**
`onboarding.review.decided` is audited (`ReviewController.php:167-172`) and pandit verification is
audited (`PanditProfileController.php:249-254`), but vendor verification is not. DoD item 5
("audit coverage present for every state change introduced") is currently violated by an existing
state change, and because the chain has no verifier (T-04) coverage is not measurable today.

**T-16 — `APPEALED` is read but unreachable.**
`ReviewController.php:24,132-133` handle `STATUS_APPEALED`, and the state exists in
`PartnerApplication.php:37-49`. **No route or service ever sets it.** The brief lists appeals as a
required safeguard ("opportunity to clarify or challenge") and the schema agrees; the API does not
exist. An applicant rejected in error has no path — which, for someone whose livelihood depends on
being bookable, is the highest-harm low-level defect in this list.

**T-17 — No data-subject rights for partners.**
`CustomerPrivacyController.php:164-180` + `ProcessCustomerErasureJob` implement erasure for
*customers*, and reference **no** Onboarding/Vendor/Pandit/Temple table. The `consent_ledger`
migration (`Customers/…/2026_11_04_000004_…php:17-30`) is devotee/notification-scoped.
`packages/analytics/src/privacy/consent-ledger.ts:13` restricts purposes to `'analytics'|'ads'`, with
an in-memory ledger only; `retention-matrix.ts:36-109` covers BigQuery/GA4 domains;
`erasure-tokens.ts` is subject-hash based. **Conclusion: there is no general-purpose consent,
retention or erasure substrate that Onboarding can use** — and Onboarding is precisely where the
sensitive data is. §10 builds it as a shared domain rather than a fourth bespoke privacy system.

**T-18 — Nothing expires, and nothing re-verifies.**
`expires_at` exists nowhere in onboarding tables; there is no expiry command (`ExpireChecksCommand`
does not exist), no re-verification on material change, no policy version recorded on a decision
(`track_version` records *form* version, not *policy* version), and no authority expiry — so a
trustee authorization, once marked, is eternally valid. Combined with T-01, the platform's trust
state is monotone: things only ever become "verified," never stale, revoked, or expired.

**T-19 — No concept of a qualification, issuer, or certificate type exists.**
`TrackCatalog.php:19-61` and `OnboardingTrackSeeder.php` define **step IDs only** (`identity`,
`credentials`, `business`, `documents`). `StepValidators::validateCertifications()` (`:60-67`) just
requires a non-empty list of strings. So "Veda Pathashala" and "Vidwat Patra" have no representation
anywhere — no issuer directory, no certificate reference, no scope, no competency assessment. The
brief's qualification-category matrix starts from zero, and the field name `certifications`
(`:62-66`) implies a free-text list, which is where it must not stay.

### 1.3 Assumptions to prove, not inherit

| # | Assumption | Proof | Gate |
| --- | --- | --- | --- |
| A-1 | Counsel has classified the platform's payment flow, fund custody and payout model (PMLA reporting-entity question) | written legal opinion | **G-LEGAL-0, blocks §8** |
| A-2 | DPDP commencement positions relied on in §10 are current | re-checked against the official notification per provision | G-PRIV-0 |
| A-3 | An authorized GST verification route exists or a manual procedure is acceptable to tax counsel | documented route or SOP | G-GST-0 |
| A-4 | A licensed provider exists for penny-drop, or manual bank proof is sufficient | provider agreement | G-BANK-0 |
| A-5 | Issuer confirmation is obtainable for the pilot certificate set | ≥10 issuers responded | G-ISSUER-1 |
| A-6 | Reviewer capacity exists for evidence-based review of the current backlog | measured queue throughput | G-OPS-0 |

A-2 deserves emphasis: the dates in the source brief (Rules notified 2025-11-14; specified
provisions commenced 2025-11-13; sections 3–17 scheduled 18 months later) are **the brief's
assertions, not facts I could verify from this repository.** Treat every §10 date as **(unverified)**
pending A-2, and note that a privacy engineering programme built on a wrong commencement reading is
still worth building — the controls below are defensible on their own terms regardless of schedule.

---

## 2. Scope and posture

### 2.1 In scope
1. Replacing the single `verification_status` with **independent, evidence-backed verification
   claims** (§4) and making every badge trace to a claim row.
2. The retrieval path so a reviewer can actually see evidence (§5) — currently the binding constraint.
3. Qualification model: issuer registry, certificate identity, scope, competency, revocation (§6).
4. Institutional existence **separate from** representative authority, as a claims graph (§7).
5. GSTIN and bank verification in **four honest modes** with real destination schema (§8).
6. Consent, retention, erasure and partner data-subject rights as a shared substrate (§10).
7. Maker-checker, policy versioning, appeals, expiry, revocation (§12–§13).
8. Turning every declared control into an enforced, falsifiable one (meta-finding).

### 2.2 Out of scope (with owner)

| Excluded | Why | Owner |
| --- | --- | --- |
| Deciding whether the platform *is* a PMLA reporting entity | legal determination, not engineering | counsel, G-LEGAL-0 |
| An implemented statutory AML programme (SAR/STR filing, designated officer, records regime) | premature until A-1; a half-built AML claim is worse than none | separate ADR after G-LEGAL-0 |
| Access to the National Database of Crime Records | restricted to authorized police officers; not available to a marketplace | never — use lawful certificate routes (§9) |
| Unrestricted Aadhaar collection / biometric storage | UIDAI consent framework; §7 of the brief | only via registered OVSE, optional, later |
| Scraping GST portal / bank sites | prohibited, and would produce unverifiable evidence | never |
| Religious or caste-based eligibility judgement | outside any platform's competence, and harmful | never |
| A general payments/payout system | `Payment`/`Finance` modules own that | those modules |

### 2.3 The four evidence modes (normative)
Every verification result MUST declare exactly one mode, and the mode is part of the badge a
customer or partner can see:

| Mode | Meaning | May display |
| --- | --- | --- |
| `native` | computed locally: format, checksum, cross-field consistency, duplicate detection | "checked by Navdhya" |
| `manual` | a named authorized reviewer examined primary evidence | "manually reviewed by <role>, <date>" |
| `official` | response from an authorized registry/issuer/credential source | "confirmed by <issuer/registry>, <date>" |
| `financial` | response from a contracted bank/payment provider channel | "account validated via <provider>, <date>" |

**The rule that follows from T-01…T-05: a claim may not be recorded at a mode higher than its
weakest required input.** A `native` format check plus a `manual` document glance can never produce
an `official`-mode badge. `penny_drop_verified` is unreachable until A-4 holds — the brief's
strongest recommendation, and the one this codebase is furthest from honouring.

### 2.4 What this document does not decide
Whether the platform's payout flow constitutes fund custody; whether FIU-India obligations attach;
which state's trust law governs a given temple; whether any particular certificate has legal status.
These are inputs (§18 E-TR-07 records them as gates), not conclusions.

---

## 3. The central architectural decision

**Today:** one mutable string per entity (`verification_status`) is the entire trust model, and six
subsystems read it for money, bookings and ranking (T-01).

**Options considered.**
- *Option B — extend the enum* (`VERIFIED` → `IDENTITY_VERIFIED | QUAL_VERIFIED | …`). Cheap, but
  it keeps the single-flag failure: combinations cannot be expressed, an approving actor cannot be
  attached to a specific claim, and expiry per claim is impossible.
- **Option A (recommended) — a claims-and-evidence kernel, with `verification_status` demoted to a
  derived projection.**

```text
app/Domain/Verification/
  Claims/      ClaimType, Claim, ClaimStatus, EvidenceTier (the 4 modes), ClaimDecision,
               ClaimScope, PolicyVersion, ExpiryPolicy
  Evidence/    Evidence, EvidenceIntegrity (sha256 verify), DocumentRetrieval, ReviewerNote
  Issuers/     IssuerRegistry, IssuerContact, IssuerResponse, CredentialRevocation
  Authority/   EntityRelationship (the graph, §7), AuthorityGrant, AuthorityExpiry
  Fiscal/      TaxIdValidator (native), TaxRegistrationVerifier (port), BankAccountVerifier (port),
               PayoutOwnership
  Identity/    PersonIdentity, IdentityFingerprint (keyed, non-reversible), NameMatcher
  Risk/        RiskSignal, RiskAssessment, EscalationRoute, ApplicabilityGate (A-1)
  Audit/       DecisionRecord (policy_version + actor + reason + evidence set),
               ChainVerifier (closes T-04, mirroring VerifyAuthEventChainCommand)

app/Modules/Onboarding/          orchestrates applications; owns NO trust semantics
app/Modules/Pandit|Temple|Vendor/ read a DERIVED projection, never a hand-set field
```

**Migration rule:** `verification_status` stays as a **read-only derived cache** for one release so
the 13 read sites in T-01 do not all change at once, recomputed from claim rows by a projector
(§13.3). It becomes non-writable: `forceFill(...'verification_status')` on a model that has claims
is a **runtime exception**, not a convention, and a test asserts it (this is the mechanism that stops
T-01 recurring the day after it is fixed).

The kernel is shared because the same claims are needed by Search (`card_json.verified` is a ranking
input), by payouts (`Finance`), by public pages, and by notifications. A per-module copy is the
D-13/P-09 mistake, and here the cost of two sources of truth is a person being bookable in one
surface and blocked in another.

---

## 4. Verification claims

### 4.1 Claim types (independent, never implicit)

| Claim | Question answered | Default evidence |
| --- | --- | --- |
| `identity.person` | is this the person they claim to be | `native` + `manual`; `official` if OVSE/DigiLocker later |
| `contact.phone_possession` | controls this number at time t | `native` (the existing OTP, correctly labelled per T-13) |
| `qualification.<type>` | holds the named award from a named issuer | `native` + **`official` issuer confirmation**, `manual` specialist review |
| `competency.practical` | can perform the rite, as assessed | `manual` by a credentialed assessor |
| `institutional.existence` | the entity exists with a verifiable governing form | `native` + `manual`/`official` registry |
| `institutional.authority` | **this person may represent this entity** | `manual` resolution/authorization review, `official` where a registry returns it |
| `fiscal.tax_registration` | GSTIN corresponds to the claimed business | `native` structural + `manual`/`official` portal evidence |
| `fiscal.payout_ownership` | account exists, holder matches, entity owns it | `financial`, else `manual` bank proof |
| `safety.background` | defined checks, defined scope | `manual` certificate review; never a criminal DB call (§9) |
| `risk.enhanced_review` | open indicators requiring senior review | `native` signal aggregation |

An application's status is a **function over this set**, never a synonym for any member. So the
brief's `{identity: verified, qualification: verified, background: pending, …, overall:
conditionally_approved}` example is representable — and `overall_status` becomes computed rather
than hand-maintained.

### 4.2 Scope, because "verified" is always relative to something
Every claim carries a `ClaimScope`: which service categories, which market, which tenant, which
role. A pandit verified for *Griha Pravesh* is not thereby verified for *Mahamrityunjaya*; a person
verified as a *representative* is not verified as a *trustee*; a vendor verified for one GSTIN is not
verified for another entity's payouts. **Booking eligibility and search rank MUST read scope, not
just status** — the 13 sites in T-01 get an explicit scope argument, and a test proves a
scope-restricted claim does not unlock an out-of-scope service.

### 4.3 Confidence, honestly separated from mode
`mode` (how it was checked) and `confidence` (how strong that evidence is) are distinct fields, so
"manually reviewed, low confidence" is expressible and cannot silently render as a green badge. The
brief's evidence-weighted qualification score
`Q = w₁I + w₂A + w₃C + w₄E + w₅P` is adopted **only as a reviewer aid for prioritisation**, never as
an auto-approval mechanism: no threshold on a composite score may set a claim to `official`-mode
verified. The strongest evidence remains confirmation from the issuer or an authoritative repository.

---

## 5. Evidence retrieval — the binding prerequisite

**Nothing else in this document will change behaviour until this section exists.** T-05 means
reviewers cannot see documents; no amount of claim modelling fixes a workflow where the evidence is
unreachable.

Required, in E-TR-01 before any policy work:

1. `GET /api/v1/onboarding/documents/{id}/content` returning a **short-lived signed, capability- and
   scope-checked stream** — reviewer must hold `onboarding:review:view`, belong to the tenant, and
   have the application assigned or claim-relevant (attribute check, not role check alone).
2. **Every open is an audited event** (`onboarding.document.accessed`: actor, document, application,
   purpose, IP, retention-safe). Today download is un-audited because it does not exist; the audit
   hook lands with the endpoint, not later.
3. Inline render for images/PDF with no persistent client copy; no public object key ever exposed
   (the content-addressed `object_key` in `application_documents` is an internal identifier, and
   leaking it in a response body is the "leaked document ID" test case the brief lists).
4. `pdf` handling: **serve as an attachment with `Content-Disposition` and a locked `Content-Type`;
   never inline-render a PDF as active content** (a PDF carrying embedded JS in an inline frame is a
   real vector). Existing `jpeg|png|pdf` whitelist (`DraftController.php:130-139`) is retained;
   add magic-byte/MIME-sniffing verification rather than trusting the declared type, and reject
   archive-in-container uploads.
5. Integrity on open: recompute sha256 and compare to the stored value (T-04). **Mismatch ⇒ claim
   cannot move to verified, and a `safety.background`-style escalation is raised.**
6. Quarantine-before-review state so a document is never rendered to a reviewer before basic
   validation passes.
7. Retention-bound deletion path (`Storage::delete` currently never called for these keys).

AC shape: GIVEN a reviewer WITHOUT the document capability WHEN they request the content URL THEN
403 and no bytes; GIVEN the same reviewer WITH capability WHEN they open a document whose stored
sha256 has been altered THEN the claim transition is blocked and an audit row records the mismatch.

---

## 6. Pandit qualification verification

### 6.1 Issuer registry (the differentiator, and it is data)
There is no universal registry for every *Vidwat Patra* or *Veda Pathashala* — the brief is right, and
assuming otherwise is how a "verified" badge becomes meaningless. So the platform maintains its own
**evidence-graded issuer directory**, which is genuinely valuable IP:

```text
verification_issuers
  id · name · name_dev · kind (pathashala|akhara|university|board|individual_guru|other)
  jurisdiction_state · established_year · registry_reference · contact_channel
  contact_verified_at · response_rate · last_confirmed_at
  evidence_grade enum(unconfirmed|contact_found|registered_entity|responding|authoritative)
  equivalence_notes · policy_version · created_at · updated_at
```

`evidence_grade` is the honest ladder the brief asks to distinguish: issuer confirmed / institution
confirmed / award confirmed / curriculum confirmed / equivalence reviewed / competency assessed.
**A credential's maximum attainable mode is capped by its issuer's grade** — an `unconfirmed` issuer
can never yield an `official` claim, only `manual` with low confidence. That single rule prevents the
"certificate PDF looks authentic" auto-approval the brief warns against, structurally rather than by
policy prose.

### 6.2 Credential model
`verification_credentials`: issuer id, `credential_type` (an enum seeded from §6.3 categories, **not
free text** — closes T-19), certificate reference (keyed HMAC fingerprint, not plaintext), award year,
programme/curriculum reference, subject scope list, mode attained, `official` response reference,
`revoked_at`, `revocation_reason`.

### 6.3 Categories (the brief's table, as data)
`VEDA_PATHASHALA · VIDWAT_PATRA · SHASTRI · ACHARYA · VEDIC_RECITATION · JYOTISHA · KARMAKANDA ·
SHASTRIC_OTHER` — each with required evidence, whether practical competency is required, and which
service categories it unlocks via `ClaimScope`. **`Jyotisha` and remedies content are handled with
particular care**: they sit adjacent to health and financial advice that users may act on, and the
platform's claim is "this person holds a documented qualification in this tradition," never "this
person's predictions are accurate."

### 6.4 Issuer confirmation workflow
Outreach is recorded, not assumed: request sent → acknowledged → response received → verdict, each
stamped, with the responder role captured. `response_rate` per issuer feeds §14 prioritisation.
NAD/DigiLocker remains an **optional** `official` route with consent captured per the brief's note
that NAD's verifier workflow expressly requires student consent — and is never the assumed path for
religious-education certificates.

### 6.5 Appeals on qualification decisions
Because an issuer may simply not respond (A-5), a non-response must yield `manual` review with
declared confidence and an appealable decision — **not** an auto-rejection. A genuine certificate from
an unreachable institution is the common real-world case and the one most likely to be mishandled by
a system that prefers clean booleans.

---

## 7. Temple existence and trustee authority

### 7.1 Two claims, one graph
Existence (`institutional.existence`) and authority (`institutional.authority`) are separate claims,
each independently evidenced and expiring — the brief's central distinction, and precisely what T-01
collapses.

```text
Entity → LegalForm → GoverningInstrument → GoverningBody → Membership(person, role, term)
       → AuthorityGrant(person, scope, resolution_ref, effective_from/to)
       → PlatformAccount → PayoutInstrument
```

Every **edge** is a claim with evidence; the whole path must hold for payouts. Legal forms supported
as distinct routes: public religious trust, registered society, Section 8 company, temple board
under a state endowment authority, statutory Devaswom board, and **unregistered institution**
(enhanced manual existence + authority review, per the brief — not a rejection).

State jurisdiction matters and is data, not code: e.g. Maharashtra public-trust registration and
trustee records are reachable through the Charity Commissioner's public "Know Your Trust" search
(`charity.maharashtra.gov.in`), which supports a **`manual`** route with a captured screenshot/reference
and a reviewer name; asserting `official` from a public web search result is not defensible, and no
automated scraping is done (§2.2). Other states require their own documented route before an
`official` claim can be granted there.

### 7.2 The trustee-takeover scenario, as a test
The brief's adversarial case — a genuine registration certificate submitted by someone who is not a
current trustee — is the module's most important acceptance test, written as: GIVEN a valid trust
registration document WHEN the applicant has no current membership row and no authority grant THEN
`institutional.existence = verified` **and** `institutional.authority = pending` **and** the
application cannot reach `APPROVED` **and** no payout instrument may attach to that entity. The test
must fail today (T-01/T-06: one click sets VERIFIED with no such linkage), which is the point of
writing it first.

### 7.3 Governance protections
Authority expiry; joint-approval requirements above a threshold; **trustee change detection**
(re-verification when a governing body changes — §13.4); bank mandate change review with a cooling
off period and dual authorization for larger institutions; conflicting trustee-claim detection
(≥1 claim, ≥2 claimants ⇒ `risk.enhanced_review`, never first-writer-wins); duplicate temple
registration detection; historical governance records retained.
All of these are **claim-graph invariants with tests**, not features — which is what distinguishes
them from the T-12 quorum enum that declares a control and enforces none.

### 7.4 Relationship graph, bounded honestly
Traversal answers operational questions — who else does this trustee represent, is this authority
expired, does this payout account belong to an unrelated legal entity, are two profiles claiming one
institutional role. It MUST remain a **claims-and-evidence graph**: an edge never implies liability,
and no inference of guilt is drawn from connectivity. §16's fraud-network feature is exactly this
graph plus reviewer-flagged evidence, with the interpretation left to a human and the appeal path
open (T-16).

---

## 8. Fiscal verification — GSTIN and bank

### 8.1 GSTIN: native checks that are actually possible offline
Replace T-09's length check with a real structural validator (pure PHP, no dependency, fully
testable): total length and character classes; **positions 1–2 = a valid state code** from a
maintained registry; **positions 3–12 = a well-formed PAN structure** (3 letters, 4 digits, 1
letter, check digit rule) with the entity-type digit at position 6; position 13 = entity count digit;
position 14 = the checksum placeholder/default character; **position 15 = the check digit, computed
and compared**. Each failure yields a distinct `reason_code` so the applicant can fix the right thing.

Then, and separately:
- **Status/legal-name comparison requires an authoritative or manual route** (A-3). The brief is
  correct that GST API onboarding is controlled and anonymous live verification cannot be assumed.
  Until then: `manual` mode with captured evidence and a reviewer name. Never `official`.
- Name matching uses Unicode-normalised token-set similarity, transliteration-aware (reuse the
  Search spec §4 scheme registry rather than a second matcher — D-13's lesson), Jaro-Winkler /
  Damerau candidates, and `native` scoring **for prioritisation only**. The authoritative returned
  legal name is the reference; **no similarity score auto-approves** (brief §3.3).
- **"GST not applicable" is a first-class path**, not an error — fixing the availability bug in
  T-09. Lawful non-registration must route to alternative business verification.
- Cancelled / suspended / not-found each get distinct outcomes (`hold`, `manual_review`,
  `correction_required`) and never a silent pass.

### 8.2 Bank: design the schema before wiring a provider
Required columns (T-11) on a `verification_bank_instruments` table —
`account_fingerprint` (keyed HMAC), `account_number_encrypted`, `ifsc_encrypted`, `account_holder_name`,
`account_type` (individual|joint|entity), `ownership_type`, `verifier_mode`, `verification_status`,
`name_match_score`, `provider_reference`, `verified_at`, `approved_at`, `approved_by`,
`supersedes_id`, `cooling_off_until`. IFSC validated natively (bank + branch lookup table is local
data, so `native` mode is honest here); account number format per RBI-published patterns.

Decision policy exactly as the brief's matrix: exact legal-name match ⇒ pass; minor normalised
difference ⇒ review; different individual ⇒ hold; inactive ⇒ reject destination; joint account ⇒
verify ownership/authority; **personal account for a temple/trust ⇒ documented policy exception with
named approver**, never a silent accept. Joint-account name response limits (SEBI's FAQ point, in
their securities context) are recorded as a *known limitation of the provider response*, not as a
platform defect.

### 8.3 The payout-substitution controls, enforced
Bind account to the specific verified entity and claim generation; re-verification on change;
cooling-off window before the new destination becomes usable for disbursement; independent approval
for institutional accounts; **freeze pending payouts on a credible ownership discrepancy**; retain the
prior approved instrument in history. The invariant to test: *a payout cannot be sent to an
instrument whose `fiscal.payout_ownership` claim is not currently valid for that entity* — which
`Finance` must consult through the kernel, not through a copied field.

### 8.4 What must never be claimed
No `penny_drop_verified` state exists in the UI or API until A-4 is satisfied; the brief's rule and
T-12's lesson are the same thing. **`native` + `manual` are legitimate, publishable answers.** Do not
scrape bank or GST portals. Do not build an unlicensed wallet or settlement path.

---

## 9. Identity, background and screening

### 9.1 Identity methods, each with a mode
PAN / passport / driving licence / voter ID / Aadhaar **offline verification via a registered OVSE**
(optional, future) / DigiLocker credential / document review / video KYC only where justified.
UIDAI's OVSE framework lists pilgrim and NGO/trust verification among offline use cases, so an
optional route is plausible for this platform — but it is a **registration and compliance project**,
not an endpoint, and it must offer a reasonable alternative method, obtain explicit consent naming
the fields and purpose, and **never retain an Aadhaar number or biometric as routine identity
storage**. Do not build an Aadhaar collection form because the team wants KYC; that is the shortest
route to a compliance problem the platform did not previously have.

### 9.2 Name matching, Indic-aware, review-prioritising
`Identity\NameMatcher` must handle Devanagari/Latin transliteration (reusing the Search spec §4
scheme registry — no second fold map), initials vs expanded forms, multiple surnames, institutional
and honorific prefixes (`Shri`, `Smt`, `Swami`, `Mahant`, `Acharya`), joint representatives, and
`K/C/`-style paternal constructions common in South India.
**Hard rule (brief §4.2, and the anti-discrimination floor): a low similarity score may never reject
a person.** It raises `risk.enhanced_review` priority. The same component must not be reachable from
an adverse-decision code path — enforced by a test asserting `NameMatcher` output cannot set a claim
to rejected.

### 9.3 Duplicate detection without a shadow identity store
`IdentityFingerprint` = keyed HMAC over normalised identity attributes, stored **as a fingerprint
only**, never the source value, with a documented key-rotation and re-derivation procedure. It powers
duplicate-pandit and one-person-many-accounts detection. Because a fingerprint is derived from
identity attributes, it is still personal data: it inherits §10 retention and is covered by erasure.

### 9.4 Background checks — independent, lawful, reviewable
No single `background_verified` checkbox. Per-check rows in `verification_background_checks`:
`check_type` (identity | qualification | experience_references | institutional_association |
address | police_antecedents | platform_fraud_indicators | disciplinary_history), lawful basis,
applicant notice + authorization reference, exact scope searched, source, search date, response
date, findings, **legal status precisely classified**, reviewer, confidence, and the applicant's
opportunity to clarify.

**The legal-status distinction is a data-model requirement, not editorial advice.** A police
complaint, a pending investigation, an arrest, a charge, a discharge, a conviction and an acquittal
are different states, so `criminal_record = true` is prohibited as a schema shape. `PoliceAntecedentStatus`
is an enum with an evidence reference per value. Where no lawful route to verify exists — including
any expectation of National Database of Crime Records access, which is restricted to authorized
police officers — the check is recorded as **`not_available`** with an explanation, which is a
legitimate outcome and must not be rendered as a failure.

Adverse findings **always** require human relevance assessment plus an appeal path (T-16), and an
unverified allegation or a fuzzy name match can never alone produce an adverse decision.

### 9.5 Screening (sanctions/PEP) — only if legally applicable
Named Indian designated-person lists and applicable UNSC consolidated lists, with source-list
version, last-checked time, match confidence, DOB and attribute corroboration, false-positive review
queue, escalation and documented resolution. **A possible match is not a confirmed match**; PEP status
is an enhanced-review indicator, never a finding of wrongdoing. Gated behind G-LEGAL-0 (§8 of the
brief and A-1): building screening before classification creates the appearance of an AML programme
without its obligations, which is worse than not having it.

---

## 10. Consent, privacy and retention

### 10.1 Why this is a shared substrate and not module-local
T-17 shows three bespoke privacy mechanisms already exist — customers (`consent_ledger`,
`CustomerPrivacyController` erasure), analytics (`packages/analytics/src/privacy/*`, analytics and
ads purposes only, in-memory ledger), and MFA (`MfaService` `Crypt` use) — and none is reusable here.
A fourth bespoke copy is the worst available outcome, so §10 specifies a **shared
`app/Domain/Privacy`** capability that Onboarding requires and Customers/analytics can migrate onto
incrementally. This is the same "one kernel, many consumers" decision as §3 and Search §3.

### 10.2 Purpose-specific consent
```text
privacy_consents
  id · subject_type · subject_id · tenant_id · purpose (enum, closed set)
  notice_version · consent_version · state (granted|withdrawn|never_given|refused)
  granted_at · withdrawn_at · evidence_reference · channel · ip_hash (nullable)
  UNIQUE (subject_type, subject_id, purpose, consent_version)
```
Purposes are a **closed enum with explicit non-cascading**: `identity_verification`,
`qualification_issuer_outreach`, `institutional_authority_review`, `tax_registration_check`,
`bank_account_validation`, `background_check`, `payout_processing`, `dispute_handling`.
Consent for issuer outreach does **not** authorize a background check, marketing, or indefinite
retention (brief §7). Withdrawal halts the dependent processing and is itself an audited event.

### 10.3 Retention, minimisation, deletion
`privacy_retention_policies` per purpose: retention clock, trigger (approval / rejection / withdrawal),
archive point, hard delete, and the legal-hold override. Applied concretely:
- **Document bytes**: shortest lawful life; deleted on rejection after the appeal window, and on
  withdrawal; `Storage::delete` finally called (T-05 item 7).
- **Financial identifiers**: encrypted at rest (T-10), masked in every UI (`••••1234`), fingerprint
  retained for anti-fraud, full value purged per policy.
- **Background evidence**: retained only while a claim is active plus the dispute window.
- **Aadhaar / biometrics**: never stored routinely (§9.1).
- Data minimisation enforced at write: a step may not persist fields its validator does not declare.

### 10.4 Data-subject rights, including for partners
Correction requests, access ("what do you hold about me and on what basis"), erasure/anonymisation,
and a **plain-language explanation of a decision** — the last being both a trust feature and the
brief's "opportunity to challenge." Coverage MUST extend to `partner_applications`,
`application_documents`, `verification_*`, `review_records`, issuer outreach records and fingerprints.
The existing `ProcessCustomerErasureJob` is the pattern to generalise, not to copy.

### 10.5 Incident and breach workflow
Security-incident capture, internal severity assessment, an evidence-preserving path, and a
notification workflow whose *conditions* are legal determinations (A-1/A-2) rather than engineering
guesses. The engineering obligation is to be able to answer "whose data, which purpose, which
subject, which time" fast enough for any notification decision to be possible at all.

### 10.6 Processor governance
Any future provider (OVSE, DigiLocker/NAD, GST, penny-drop, screening, police route) requires a
recorded processor agreement, purpose binding, sub-processor note and deletion confirmation *before*
its adapter is enabled — enforced by a config gate rather than a checklist:
`verification_provider_registry.status = approved` is required to register an adapter, closing the
T-12 class at the wiring layer.

### 10.7 Commencement honesty
A-2. The brief's DPDP dates are **(unverified)** from this repository's evidence. Every §10 control
above is defensible as good practice irrespective of schedule; the **deadlines, notices and
cross-border terms** are not engineering claims. The spec's posture: implement now, date the
obligations from counsel, and never present "DPDP compliant" as a status the code asserts.

---

## 11. State machine, decisions and dual control

### 11.1 Application vs claim — two machines, never one
`APPROVED_LIMITED` (brief) is expressible only if the two are separate: a pandit may be approved to
*display a profile* while `fiscal.payout_ownership` is pending, so payouts stay disabled. Today
`PartnerApplication.status` carries everything, and `TempleManagementService.php:129-132` additionally
flips entity `status` as a side effect (T-14) — three concerns in one write.
`verification_status` becomes **derived**; entity publication `status` becomes its own decision with
its own audit; claims get their own lifecycle.

### 11.2 Transitions are data with guards
States: `DRAFT · SUBMITTED · CONSENT_PENDING · VERIFICATION_PENDING · IN_REVIEW · ACTION_REQUIRED ·
ENHANCED_REVIEW · APPROVED_LIMITED · APPROVED · REJECTED · SUSPENDED · EXPIRED · REVOKED · APPEALED ·
WITHDRAWN`. Each transition declares: allowed predecessors, **required claims with required modes**,
required actor capability, mandatory reason, notification event, and audit record. Enforced by a
transition table + guard, not scattered `if`s — and the guard is tested by attempting every illegal
edge (the falsifiable-control rule).

### 11.3 Dual control, actually enforced (fixes T-06/T-08)
```text
onboarding_decisions
  id · application_id · decision · reason_code · note · policy_version
  actor_id · actor_role · evidence_set_hash · decided_at
  countersign_actor_id · countersign_at · countersign_policy_version
  UNIQUE (application_id, decision, actor_id)          -- one decision per actor per application
```
Rules, each with a test that fails if enforcement is removed:
1. **Approval requires a countersignature** from a *distinct authenticated* actor holding
   `onboarding:review:approve` — server-resolved, never a client string (the T-06 inversion fixed).
2. **Maker-checker**: an actor may not countersign a decision on an application whose
   `identity_id` matches theirs, nor one they initiated (T-08).
3. High-risk operations — payout instrument change, institutional authority grant, revocation,
   adverse background outcome — require **step-up plus countersign**, and the declared
   `OtpRiskTier` T3 quorum is either **enforced or deleted** (T-12); a third state is not permitted.
4. Reject keeps its countersign requirement but the UI is fixed (T-07) and the field becomes an
   authentication result, not an input.
5. `evidence_set_hash` binds the decision to the exact evidence reviewed, so a decision can be
   reproduced and a post-hoc document swap is detectable.

### 11.4 Appeals (fixes T-16)
`onboarding_appeals`: application, decision reference, applicant statement, new evidence references,
grounds enum, assigned reviewer (must differ from the original decider — same guard as §11.3.2),
outcome, and a deadline SLA. Appeals are the corrective path for the whole `manual`-mode estate:
because most claims here will initially rest on human review of unreachable documents, the appeal
route is not a courtesy, it is the control that makes the rest defensible.

---

## 12. Reviewer operations

### 12.1 Workload-aware, skill-aware routing
Priority as the brief frames it — `Risk`, `Age`, `Urgency`, `Complexity` — as a **config-driven,
logged function**, with the computed components visible on the queue row so a reviewer can see why a
case is on top and a supervisor can audit the routing. Skill routing to qualification / business /
institutional / (post-G-LEGAL-0) compliance teams, with the assignment reason recorded.

### 12.2 Reviewer quality analytics, without turning it into surveillance
Per-reviewer: agreement-with-consensus rate, overturn-on-appeal rate, evidence-completeness rate,
decision latency, countersign participation. Used to target training and to detect the T-01 pattern
(a reviewer approving fast without opening documents — which §5's access audit finally makes
*measurable*: `accessed` events now precede `decided` events for a document-backed claim, and a
decision with no prior access is flagged).

### 12.3 The review workspace
Claim checklist with **mode badges** per §2.3 (a customer-visible vocabulary, not internal jargon),
inline evidence viewer with integrity status, issuer response timeline, authority graph for
institutional cases, payout instrument with holder-match detail, decision form enforcing reason codes
and countersign, an "explain to applicant" preview, and a pending-appeals view. Nothing in this UI may
compute a trust decision (§13 rule, brief §12).

---

## 13. Lifecycle: expiry, renewal, revocation, change detection

### 13.1 Every claim expires or is explicitly eternal-with-review
`ExpiryPolicy` per claim type: certificate claims expire or require re-confirmation; authority grants
expire with the underlying term/resolution; `identity.person` may be long-lived but needs periodic
re-check where risk warrants; `contact.phone_possession` is a **time-stamped** fact, not a state.
`verification:expire` scheduled; `EXPIRED` becomes a real, reachable state (T-18).

### 13.2 Revocation with scope and reason
Issuer-confirmed credential revocation, trustee removal, payout instrument compromise, policy
regression. Revocation cascades **by claim, not globally**: revoking `fiscal.payout_ownership`
disables payouts but does not silently unverify a qualification. Derived projection recomputes and
`Finance`/`Booking`/search consumers observe the change through their own gates.

### 13.3 Derived projection with a write block (the T-01 fix)
`verification:project` recomputes `verification_status` (+ `card_json.verified`) from claims.
Models with claims **throw on direct assignment** to `verification_status` — a runtime guard, tested.
Backfill: existing `VERIFIED` rows become `legacy_asserted` claims with `mode = manual`,
`confidence = unassessed`, `actor = unknown`, **requiring re-review before promotion** to a
higher mode. Do not silently reinterpret legacy flags as evidence-backed, and do not silently drop
livelihoods: the honest interim badge for a legacy-asserted pandit is a distinct, lower-confidence
state with a re-review prompt, planned as an explicit migration with a communications plan (§20 R-02).

### 13.4 Material-change triggered re-verification
Entity legal-name change, governing body change, payout instrument change, service-category addition,
jurisdiction move, issuer equivalence change ⇒ new claims required for the affected scope only.
Trustee change detection consumes registry/manual re-checks on a schedule rather than assuming an
API exists.

---

## 14. Data model (additive summary)

```text
verification_claims · verification_claim_scopes · verification_evidence
verification_credentials · verification_issuers · verification_credential_responses
verification_entity_relationships · verification_authority_grants
verification_bank_instruments · verification_tax_registrations
verification_background_checks · verification_risk_signals · verification_risk_assessments
verification_policy_versions · onboarding_decisions · onboarding_appeals
privacy_consents · privacy_retention_policies · privacy_subject_requests
verification_provider_registry
application_documents (+ integrity_last_checked_at, integrity_state)
```
Every table: `tenant_id` NOT NULL or an explicit platform-global marker, `policy_version`,
`created_by`/`updated_by`, `created_at`/`updated_at`, and inclusion in the `AssertTenantIsolation`
matrix. Sensitive columns typed and encrypted individually (T-10 ends the JSON-blob era for
fiscal identifiers); fingerprints keyed-HMAC; no plaintext account numbers, no Aadhaar storage.
All writes that change a decision or claim go through the existing `AuditWriter` in the **same
transaction** — the pattern already proven at `ReviewController.php:167-172`, extended to cover
`VendorManagementService::verify` (T-15).

---

## 15. API contracts

Preserving the module's existing envelope (`{data, meta}` + OpenAPI fragment), planes and named
limiters, with new `onboarding:*` permissions declared in `module.json` (currently 6; add
`onboarding:document:access`, `onboarding:appeal:submit`, `onboarding:appeal:decide`,
`onboarding:claim:grant`, `onboarding:claim:revoke`, `onboarding:payout:approve`,
`onboarding:consent:manage`, `onboarding:subject:request`, `onboarding:policy:manage` — the
sensitive ones step-up gated).

| Method | Path | Notes |
| --- | --- | --- |
| POST/GET/PATCH | `/api/v1/onboarding/applications…` | existing |
| POST | `/api/v1/onboarding/applications/{id}/submit` | existing OTP |
| POST | `/api/v1/onboarding/consents` | purpose-scoped grant, versioned |
| POST | `/api/v1/onboarding/documents` | existing intake (10 MB, mime whitelist, sha256) |
| **GET** | `/api/v1/onboarding/documents/{id}/content` | **§5 — new, audited, integrity-checked** |
| GET | `/api/v1/onboarding/claims?application=` | claim set with modes and scopes |
| POST | `/api/v1/onboarding/credentials/verify` | requests issuer confirmation |
| POST | `/api/v1/onboarding/institutions/verify` | existence **and** authority, separately reported |
| POST | `/api/v1/onboarding/tax/verify` | `native` always; authoritative only when registered |
| POST | `/api/v1/onboarding/bank/verify` | mode depends on A-4; never claims `financial` without it |
| POST | `/api/v1/admin/onboarding/decisions` | countersigned, policy-versioned (replaces the current single-actor `approve`) |
| POST | `/api/v1/admin/onboarding/revocations` | claim-scoped cascade |
| **POST** | `/api/v1/onboarding/appeals` | **T-16 — new, applicant-facing** |
| GET | `/api/v1/me/onboarding-status` | what the partner sees: per-claim state, mode, next action |
| GET/POST | `/api/v1/me/privacy/{access,correction,erasure}` | §10.4 |

Provider callbacks: authenticated, signature-verified where supported, idempotent, replay-protected,
and **never able to set a claim directly** — they write an evidence row and hand to the policy engine
(brief §13). Responses never expose another subject's data, another tenant's claims, plaintext
identifiers, or a mode the weakest input cannot support.

---

## 16. Advanced capabilities worth building (in order)

Issuer registry (§6.1 — the durable differentiator) · qualification **equivalence** engine comparing
awards without pretending they are identical · credential revocation tracking · trustee-change
monitoring · bank-change risk engine · multi-entity duplicate detection · verification expiry
intelligence · reviewer quality analytics (§12.2) · evidence provenance (already half-built via
`evidence_set_hash`) · compliance policy versioning so historical decisions reproduce · role-scoped
service credentials · institutional mandate management separating trustee status from signing
authority · **fraud-network detection as graph traversal over flagged evidence, reviewer-interpreted,
appealable** (§7.4 — the inference limit is a hard requirement, not a caveat).

---

## 17. Instrumentation

Reuses the Search spec E-SR-01 transport (`packages/analytics` registry + collector), never a new
pipeline. Trust events differ from search events in one important way: **they are the evidence that
controls were enforced**, so they are retained longer and are audit-grade.

```text
onboarding.application.{created,submitted,withdrawn}
onboarding.consent.{granted,withdrawn}
onboarding.document.{uploaded,accessed,integrity_failed}
onboarding.claim.{opened,evidence_attached,decided,expired,revoked}
onboarding.issuer.{contacted,response_received,response_refused}
onboarding.decision.{approved,countersigned,rejected,escalated,overturned_on_appeal}
onboarding.appeal.{submitted,resolved}
onboarding.payout.{instrument_changed,frozen,reactivated}
onboarding.control.{attempted_bypass,segregation_violation}
verification.provider_callback.{received,rejected,replayed}
```

| Metric | Why it is the number that matters |
| --- | --- |
| claim mode distribution (native/manual/official) | whether "verified" is earning its word; the anti-T-01 KPI |
| decisions **preceded by a document access** | whether review is real or rubber-stamped |
| countersign completion rate, self-countersign attempt rate | T-06/T-08 enforcement health |
| issuer response rate + latency per issuer | registry value, and workload truth (A-5) |
| appeal rate, **appeal overturn rate**, overturn reason mix | whether decisions are right; the single best quality signal |
| time-to-decision by track × mode × complexity | partner livelihood, not vanity |
| abandonment by step + validator reason | T-09's GST availability bug would show as a spike here |
| integrity failures detected | T-04 closure |
| unauthorized cross-tenant claim reads | **zero-tolerance metric**, G-TENANT |
| claims expired without renewal; pending payouts frozen | lifecycle is real, not decorative |
| control-bypass attempts | falsifiable-control monitoring |

Privacy floor: telemetry carries identifiers and counts, never document contents, never fiscal
values, never a name; `onboarding.document.accessed` records *which* document, not what it said.

---

## 18. Work breakdown

Estimate classes per methodology §1; scaffold task first per epic; tasks need the full 9-section
template before `Approved`.

### E-TR-01 — Evidence reachability and truth in badges *(P0 — do nothing else first)*

| Task | Title | Est | Key AC |
| --- | --- | --- | --- |
| T-TR01-01 | Signed, capability-checked, audited document retrieval endpoint (§5) | L | AC-1 GIVEN a reviewer without `onboarding:document:access` WHEN they request the content URL THEN 403 and zero bytes. AC-2 every open writes an audit row with actor + purpose. AC-3 no public path or persistent object URL is ever returned. |
| T-TR01-02 | Integrity verification on read + `verification:integrity` command (T-04) | M | AC: an altered stored object blocks the claim transition and raises an escalation; general `audit_logs` chain gains a verifier mirroring `VerifyAuthEventChainCommand` and exits non-zero naming the break. |
| T-TR01-03 | PDF/image safe serving: `Content-Disposition`, locked type, magic-byte sniffing | M | AC: a polyglot/renamed file is rejected by sniffing, not by extension; PDFs never render as active content. |
| T-TR01-04 | **Fix the UI→API seam for reject (T-07)** + add seam tests | S | AC: reject from `ReviewActions.tsx` returns 2xx with a real countersignature; a Playwright test traverses approve, reject, appeal and proves no `required` field is sent empty. **Class rule: every admin action gets a seam test in the PR that adds it.** |
| T-TR01-05 | Write-block on `verification_status` + derived projector + legacy migration plan (§13.3) | L | AC-1 direct assignment throws on a model with claims (test). AC-2 legacy rows land as `legacy_asserted`/`mode=manual`/`confidence=unassessed` and cannot display an `official`-mode badge. AC-3 booking/search/public-page gates read the projection, not the hand-set field. |
| T-TR01-06 | Remove the `Temple.verify()` → `status=ACTIVE` side effect; separate publication decision (T-14) | S | AC: verifying an institution does not publish it; each action audited separately. |
| T-TR01-07 | Audit vendor verification; make coverage measurable (T-15) | S | AC: audit-coverage report lists every sensitive state change with a writer; missing coverage fails CI. |

### E-TR-02 — Claims kernel & decision records (§3, §4, §11)
T-TR02-01 `app/Domain/Verification` scaffold + Deptrac layer; T-TR02-02 claims/scopes/modes/policy
versions tables; T-TR02-03 `onboarding_decisions` with countersign + `evidence_set_hash`;
T-TR02-04 maker-checker and self-approval guards (T-06, T-08) with bypass-attempt telemetry;
T-TR02-05 transition table + guards, illegal-edge tests; T-TR02-06 `ApplicationDocument` verified/
rejected writers + `reason_code` population (T-03); T-TR02-07 enforce-or-delete `OtpRiskTier` T3
(T-12).

### E-TR-03 — Qualification verification
T-TR03-01 issuer registry + evidence grades; T-TR03-02 credential types as a closed enum (T-19);
T-TR03-03 issuer outreach workflow; T-TR03-04 mode cap by issuer grade (§6.1 rule);
T-TR03-05 specialist competency review; T-TR03-06 equivalence notes model; T-TR03-07 revocation;
T-TR03-08 optional NAD/DigiLocker consent path (gated, later).
AC: the ten "Pandit qualification" adversarial cases in §19.3 each have a named test; non-response
from an issuer yields `manual` + appeal, never auto-reject.

### E-TR-04 — Institutions and authority
T-TR04-01 legal-form registry incl. unregistered route; T-TR04-02 relationship graph edges as claims;
T-TR04-03 authority grants with terms/expiry; T-TR04-04 state jurisdiction verification routes as
documented `manual` procedures; T-TR04-05 conflicting-trustee detection; T-TR04-06 duplicate temple
detection; T-TR04-07 dual authorization + cooling-off for institutional payout changes.
AC: **§7.2 trustee-takeover test — must fail before the epic and pass after.**

### E-TR-05 — Fiscal
T-TR05-01 real GSTIN structural validator with per-position reason codes (T-09);
T-TR05-02 **"GST not applicable" path** so lawful vendors can apply (T-09 availability bug);
T-TR05-03 `verification_tax_registrations` + manual evidence capture; T-TR05-04 transliteration-aware
name matcher reusing Search §4 (no second fold map), review-prioritising only;
T-TR05-05 `verification_bank_instruments` with field-level encryption + masking (T-10, T-11);
T-TR05-06 payout-ownership invariant enforced in `Finance`; T-TR05-07 provider adapter interface
gated by `verification_provider_registry`; T-TR05-08 remove `evidence` JSON as a storage location for
fiscal identifiers, with backfill.

### E-TR-06 — Privacy substrate (§10) — shared `app/Domain/Privacy`
T-TR06-01 purpose-scoped consents with non-cascading enum; T-TR06-02 retention policies + document
byte deletion path; T-TR06-03 partner data-subject requests (access/correction/erasure) generalising
the customer job; T-TR06-04 fingerprint-in-erasure coverage; T-TR06-05 incident/breach workflow
scaffolding; T-TR06-06 processor registry; T-TR06-07 counsel-checked commencement mapping (A-2,
**recorded as an input, not asserted**).

### E-TR-07 — Risk, background, appeals, lifecycle
T-TR07-01 per-check background rows with precise legal-status enum (no `criminal_record` flag);
T-TR07-02 adverse-finding human review + appeal linkage; T-TR07-03 **appeals endpoint + queue +
independent decider (T-16)**; T-TR07-04 expiry engine + `verification:expire` (T-18);
T-TR07-05 revocation cascade by claim; T-TR07-06 **applicability gate artifact for A-1** — the
codebase records the classification result and refuses to enable statutory-AML features without it;
T-TR07-07 screening behind that gate; T-TR07-08 reviewer quality analytics.

### E-TR-08 — Reviewer experience & partner UX
T-TR08-01 review workspace with mode badges + inline evidence + integrity state; T-TR08-02 skill/
workload routing (logged, inspectable); T-TR08-03 partner-facing status = per-claim truth + next
action; T-TR08-04 "explain this decision" applicant view; T-TR08-05 replace marketing badge text
with data-derived badges (T-02); T-TR08-06 status polling/`revalidate` (absent per the sweep).

---

## 19. Acceptance gates

| Gate | Blocks | Evidence |
| --- | --- | --- |
| **G-EVID-0** | **every other gate** | A reviewer can open the exact evidence a decision cites, with integrity checked and access audited. §5 complete, T-04/T-05 closed. Until then no claim may be recorded above `native`. |
| **G-CLAIM-1** | badge display | every `verified` display traces to a claim row with mode, scope, actor, policy version, expiry; zero direct writes to `verification_status` (runtime guard, tested) |
| **G-DUAL-1** | approvals | countersignature required and authenticated for approval; maker-checker holds; self-approval impossible; **each proven by a test that passes only when enforcement exists** — the anti-T-12 rule |
| **G-SEAM-1** | any admin/partner action | every action exercised end-to-end from its own UI in CI (T-07, and the same class as Search D-11 and Calendar P-10) |
| **G-ISSUER-1** | `official`-mode qualification claims | A-5 met for the pilot set; grade ladder visible; mode capped by grade in tests |
| **G-AUTH-1** | temple onboarding | existence and authority provably separate; §7.2 takeover case fails-then-passes |
| **G-FISCAL-1** | vendor payouts | structural validation real; non-GST vendors can apply; payout cannot be sent to an instrument lacking a valid `fiscal.payout_ownership` claim |
| **G-PRIV-0** | new sensitive collection | counsel-confirmed basis, purpose, retention, and an alternative route for every identity method; **no Aadhaar or biometric routine storage** |
| **G-PRIV-1** | partner data at scale | consent purposes non-cascading; erasure covers partner applications, documents, claims, outreach, fingerprints; access/correction paths live |
| **G-LEGAL-0** | anything in §9.5 / statutory-AML features | written classification of payment flow, fund custody, payout model; feature flags cannot enable AML code without it |
| **G-TENANT-0** | every epic | zero cross-tenant claim or document access; `AssertTenantIsolation` extended to all §14 tables; negative AC per task |
| **G-AUDIT-1** | release | `audit_logs` chain verifier green; 100 % audit coverage of sensitive state changes, reported not asserted |
| **G-APPEAL-1** | any adverse decision capability | an applicant can appeal, an independent decider resolves it, outcomes and overturn reasons are measured |
| **G-LIFE-1** | lifecycle claims | expiry, revocation and re-verification reachable and scheduled; `EXPIRED` is not a dead enum |
| **G-OPS-0** | flipping review from flag-based to evidence-based | measured queue throughput at evidence-based review; staffing plan before the flag path is removed, else applications simply stall |

**Release train.** *Step 1* = E-TR-01 (evidence reachable, badges honest, seam fixed, side effects
removed) — **this is already a material safety improvement and ships alone**. *Step 2* = E-TR-02+06
(claims kernel, decisions, privacy substrate). *Step 3* = E-TR-03+04 (qualifications, institutions).
*Step 4* = E-TR-05+07 (fiscal, appeals, lifecycle, risk minus AML). *Step 5* = E-TR-08 UX.
**Do not attempt the full brief at once.** §19.4 note: replacing a one-click flag with evidence-based
review is a capacity intervention as much as a code one; G-OPS-0 exists so the safe design does not
accidentally freeze partner onboarding, which would itself be a harm.

### 19.3 Adversarial test catalogue (mandatory, from brief §16 + this module's own findings)
**Qualification:** genuine + recognized issuer · genuine from unknown institution · fake certificate
number · altered image · same certificate across accounts · valid cert, wrong scope · issuer defunct ·
issuer denies · ambiguous *Vidwat Patra* issuer · certificate under a different transliteration.
**Institution:** valid trust + current trustee · expired authorization · former trustee applying ·
two competing claims · statutory board order · government temple board · society not a trust ·
unregistered establishment · bank account in a different legal name.
**Fiscal:** active / cancelled / suspended GSTIN · invalid checksum · legal-name mismatch · multiple
trade names · GST-not-applicable vendor · account-substitution attempt · joint account holder.
**Security & integrity:** leaked document ID · unauthorized reviewer · expired consent · replayed and
forged callbacks · duplicate-application race · concurrent approvals · repeated malicious uploads ·
rollback mid-decision · key rotation · **self-approval attempt** · **countersign impersonation
attempt** · decision recorded with no prior document access.

---

## 20. Risks & kill criteria

| # | Risk | Control | Kill / revert |
| --- | --- | --- | --- |
| R-01 | UI→API seam bugs hide controls (third occurrence already) | per-action seam tests in the same PR, G-SEAM-1 | an admin action shipping without a seam test is rejected |
| R-02 | Removing the flag path stalls onboarding and hurts partners' livelihoods | G-OPS-0 capacity check, parallel-run with `legacy_asserted`, comms plan, phased cutover | queue age > 2× baseline ⇒ revert to flag path **with badges downgraded**, never with badges intact |
| R-03 | A document endpoint becomes the leak | capability + scope + audit + short-lived signed URLs + no public keys | any unauthenticated byte access ⇒ endpoint disabled immediately |
| R-04 | Declared-but-unenforced controls recur | falsifiable-test rule (§1.2 meta-finding), enforce-or-delete policy | a control with no failing-without-enforcement test cannot merge |
| R-05 | Unverified claims displayed as verified | mode caps, projector-only writes, appeal route | badge text without a claim row is a P1 product defect |
| R-06 | Privacy posture wrong because commencement dates are wrong | A-2 counsel input; controls implemented on merit, dates not asserted | legal review pending ⇒ collect less, not more |
| R-07 | Building AML-shaped compliance without legal standing | G-LEGAL-0 feature gate, no SAR/STR surface until classified | gate cannot be bypassed by config |
| R-08 | Fuzzy name matching causes wrongful rejection | score prioritises review only; no adverse path from `NameMatcher`; test asserts it | any adverse decision citing a similarity score is a blocker |
| R-09 | Discriminatory risk signals (caste, sect, accent, region, belief) | §2.2 prohibition; risk-signal registry with a lawful-justification field; review of every added signal | a signal without recorded justification cannot ship |
| R-10 | Reviewer rubber-stamping persists even with documents | access-before-decision metric, countersign, reviewer analytics, spot re-review | approval-without-access rate above threshold ⇒ workflow suspended for that team |
| R-11 | Provider dependency smuggled in via an adapter | `verification_provider_registry` gate, purpose binding | an unregistered adapter cannot bind |
| R-12 | Payout substitution after approval | cooling-off, dual authorization, freeze-on-discrepancy, instrument history | an unverified instrument can transact ⇒ payouts disabled |
| R-13 | Encryption/key model becomes a false sense of safety | keys separated from data, rotation + recovery documented, DB roles, append-only controls, chain verification | plaintext fiscal identifiers found in a dump ⇒ release blocked |
| R-14 | "Verified" read as religious endorsement | scope language, mode language, methodology page, appeal, no outcome guarantees | any badge copy implying religious authority is a review blocker |

**Verdict.** Feasible and genuinely valuable in Laravel + Next.js with zero mandatory external
infrastructure, because `native` + `manual` modes are legitimate. But the current state is not
"immature onboarding" — it is **a trust claim system whose assertions are not backed by evidence**,
and the brief's own closing principle ("every approval explainable, auditable, reversible, limited to
exactly what was verified") is the correct target and is measured by G-CLAIM-1 plus G-EVID-0.

**Sequencing claim.** Two files change the safety profile of this whole platform: a document retrieval
endpoint (E-TR-01.01) and a write block plus projector on `verification_status` (E-TR-01.05). The
first makes review possible; the second makes the badge honest. Issuer registries, authority graphs,
penny-drop adapters and screening programmes all come after, and none of them matter while reviewers
cannot open a PDF.

---

## 21. ADRs required before approval

| ADR | Subject | Must record |
| --- | --- | --- |
| ADR-0041 | Verification claims kernel; `verification_status` becomes derived | the §3 decision, the mode ladder (§2.3), the runtime write block, the legacy-asserted migration |
| ADR-0042 | Evidence retrieval, integrity and access auditing | §5, integrity-on-read, access-before-decision, key separation and rotation |
| ADR-0043 | Dual control, maker-checker and policy-versioned decisions | §11.3 rules incl. enforce-or-delete for `OtpRiskTier` T3 |
| ADR-0044 | Qualification issuer registry and evidence grading | §6, mode cap by issuer grade, no auto-approval from composite score |
| ADR-0045 | Institutional existence vs representative authority | §7, jurisdiction routes as documented procedures, trustee-change re-verification |
| ADR-0046 | Fiscal identifiers: encryption, masking, payout ownership | §8, the payout invariant, `evidence` JSON retirement |
| ADR-0047 | Shared privacy substrate and partner data-subject rights | §10, non-cascading purposes, retention, erasure scope |
| ADR-0048 | Regulatory applicability gate (AML/sanctions) | G-LEGAL-0 as a machine-checkable precondition; no AML features without counsel classification |
| ADR-0049 | Falsifiable-controls rule for this domain | the §1.2 meta-finding and R-04: no declared control ships without a test that fails when enforcement is absent |

Also required on merge: `module.json` permission/event/scope deltas (§15), `onboarding.application.*`
event schemas, OpenAPI fragment updates, Deptrac layers for `Verification` and `Privacy`,
`docs/06-tenancy/tenancy-and-data.md` matrix extension, and a badge-vocabulary section in the
product docs so marketing copy and data cannot diverge again (T-02).

---

## 22. Verification (this document)

1. Every `T-` row cites a `file:line` that still resolves; claims that could not be verified are
   labelled **(unverified)** in §1.3/§10.7 and are not used as requirements.
2. Every epic in §18 maps to at least one gate in §19; every gate has a test that fails when the
   control is absent (the anti-T-12 rule, applied to this document's own claims).
3. Every AC written into `docs/specs/tasks/` follows methodology §3 — observable, decidable,
   CI-testable, falsifiable; "appropriate", "reasonable", "user-friendly" do not appear.
4. Every task touching tenant data carries a tenancy AC; every task touching authorization carries a
   negative AC; every task touching sensitive collection carries a lawful-basis AC.
5. Every referenced event/permission/scope exists in a manifest or is added in the same task.
6. No legal obligation is asserted as engineering fact; A-1 and A-2 remain open inputs, and no
   feature that depends on them can be gated on code review alone.
7. No religious, caste, sect or belief-based attribute is used as a risk signal anywhere in the
   design, and §20 R-09 keeps that testable.
8. Doc drift is a review blocker; a behaviour change without a change here fails DoD item 7.
