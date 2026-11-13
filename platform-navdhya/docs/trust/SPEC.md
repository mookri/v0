# Trust, Support & Devotee Satisfaction — Feedback, Support & Incident Specification v1

**Modules:** new `app/Modules/Feedback`, `app/Modules/Support`, `app/Modules/Incident` (slices S145–S164)
— extracted from `Booking` (reviews) and `Customers` (tickets), with `packages/incident` **extended
rather than replaced**
**Packages:** `@navdhya/feedback`, `@navdhya/support`, `@navdhya/incident`, new `@navdhya/trust-contracts`
**Status:** Implemented (Slices S145–S164 · ADRs 0059–0067 · Verified on PHPUnit, Deptrac, Vitest & Jest).
**Extends / relates:** [`docs/onboarding/SPEC.md`](../onboarding/SPEC.md) (qualification claims, safety
of a person, appeals), [`docs/communications/SPEC.md`](../communications/SPEC.md) (notification
delivery, journey events), [`docs/search/SPEC.md`](../search/SPEC.md) (rating as a ranking input),
[`docs/seo-content/SPEC.md`](../seo-content/SPEC.md) (event-producer discipline)
**Methodology:** every task in §21 follows [`docs/specs/README.md`](../specs/README.md) §2 template,
§3 AC shapes, §4 Definition of Done.

---

## 0. How to read this document

```text
Epic       E-TS-<nn>           e.g. E-TS-02
Task       T-TS<epic>-<nn>     e.g. T-TS02-03
AC         AC-T-TS02-03-<n>
Defect     TS-<nn>             verified defect in shipped trust code (§1.2)
Subject    feedback subject    = pandit | temple | vendor | service-instance
Case       support case        = one devotee or provider request, formally tracked
Incident   operational incident = one disruption, possibly affecting many bookings
```

Normative words: **MUST** = blocker, **SHOULD** = justified deviation in the PR, **MAY** = optional.
§1 is facts verified against this working tree on 2026-11-10, each re-read directly. Legal and
standards positions from the source brief are marked **(unverified here)** — this repository contains
no copy of any of them (grep for `grievance|redressal|consumerhelpline|dark.?pattern` across
`app/`, `packages/`, `docs/04-security`, `docs/support`, `docs/trust` returns **zero matches**), and
§10 routes them to counsel rather than treating them as engineering facts.

A word on what this module actually is. The other three specs compute things — a tithi, a prefix
range, a delivery receipt. This one adjudicates **between people**, under a heading of "trust," in a
domain where the parties are performing religious service and are often mid-ceremony, stressed, and
asymmetrically powerful. Two failure directions matter equally and the design tries to hold both:

1. A devotee who received poor service cannot get a resolution, is steered into a star rating that
   changes nothing, or is told the case is closed when it isn't.
2. A pandit whose livelihood is a scalar `rating_avg` is down-ranked or suspended by an unverified
   rating, a venue failure counted as their fault, or a complaint nobody reviewed — with no evidence,
   no response right, and no appeal.

The brief's own adversarial questions (§26.4) are the right test, and §22 turns each into a gate.

---

## 1. Evidence baseline

### 1.1 What actually ships

| Concern | Shipped reality | Verified at |
| --- | --- | --- |
| Reviews | `booking_reviews`: `ulid id`, `tenant_id`, **`booking_id` UNIQUE**, `user_id`, `rating` (tinyint), `comment` nullable | `Booking/Database/Migrations/2026_10_01_000003_…:14-24` |
| Review API | create/read through `BookingReviewController` | `Booking/Http/Controllers/BookingReviewController.php` |
| Aggregation | `RecomputePanditRatingsCommand` → `rating_avg`, `rating_count`, `total_bookings` from completed assignments + reviews | `Pandit/Console/RecomputePanditRatingsCommand.php:20-70` |
| Rating display | `card_json.rating` / `review_count` flow into search documents | `Search/Contracts/PanditSearchProvider.php` (see Search spec §1.1) |
| Support | `support_tickets`: `id`, `tenant_id`, `user_id`, `subject(190)`, `category(32)`, **`status varchar(16)` default `OPEN`** | `Customers/…/2026_10_01_000008_…:16-27` |
| Case states | exactly two: `STATUS_OPEN`, `STATUS_CLOSED` | `Customers/Models/SupportTicket.php:31,33` |
| Categories | exactly four: `['billing','booking','account','other']` | `SupportTicket.php:38` |
| Staff actions | `index`, `show`, `reply` (reopens a closed ticket), `close` (flips status, no outcome) | `Customers/Http/Controllers/StaffSupportController.php:39,78,114,129-131,162-181` |
| Messages | `support_messages` table | migration `…000009…` |
| Booking lifecycle | includes `STATUS_NO_SHOW` and a legal transition `ASSIGNED → NO_SHOW`; `NO_SHOW` is terminal | `Booking/Domain/BookingLifecycle.php:35,43,50,73` |
| Scheduled time | `bookings.scheduled_at` — **nullable timestamp** | `Booking/…/2026_10_01_000001_create_bookings_table.php:22` |
| Assignments | `pandit_assignments`: `status` (PENDING/CONFIRMED/DECLINED/COMPLETED/CANCELLED), `assigned_at`, `confirmed_at`, `declined_at`, `decline_reason`, `compensation_minor`, `UNIQUE(pandit_id, booking_id)` | `Pandit/…/2026_10_04_123555_…:14-36` |
| Cancellation / reschedule use cases | exist, with locking, audit and outbox writes | `Booking/Application/CancelBookingUseCase.php:66-176`, `PreviewRescheduleUseCase.php`, `PreviewCancellationUseCase.php` |
| `@navdhya/feedback` | 230 LOC: `RatingAggregate{count,sum,distribution}`, `addRating`, `averageRating` (naive mean), `QualitySignal` | `packages/feedback/src/quality/quality.ts:1-28` |
| `@navdhya/support` | 188 LOC: case, conversation, resolution, tasks, dispute contracts | `packages/support/src/**` |
| `@navdhya/incident` | 196 LOC: **an SRE-shaped model** — `Alert`/`AlertFingerprint`, `OperationalControl`, `IncidentSeverity = SEV_1..SEV_4`, `CommandRole = COMMANDER|TECHNICAL_LEAD|COMMUNICATIONS_LEAD|RECORDER`, `PublicStatusPage` (`INVESTIGATING|IDENTIFIED|MONITORING|RESOLVED`), `Postmortem`, `CorrectiveAction`, `slo.ts` | `packages/incident/src/domain/*.ts`, `reliability/slo.ts` |

**Two genuinely good assets, which the design builds on rather than replacing:**
1. **`booking_id UNIQUE`** already enforces the brief's most important eligibility rule (§3.1 item 4,
   "no duplicate review for the same service instance") at the database level. Most implementations
   get this wrong; this one has it. §5 preserves and extends it.
2. **`@navdhya/incident` already has the right bones** for the brief's §7: typed severity, an owner
   role model, alert dedup fingerprints, timeline, postmortem, corrective actions, a public status
   page. This is an asset worth ~196 LOC of contract and a lot of design thinking, and the brief
   treats incidents as greenfield. §12 says: **generalise it, do not rewrite it.**
3. Also good: `CancelBookingUseCase` demonstrates the house pattern this module needs repeatedly —
   row lock, guard, status write, audit row, outbox event (`:66-176`). §9.4 and §11 reuse that shape
   rather than inventing a case-transition mechanism.

### 1.2 Defect register

Each row re-read in this tree.

**TS-01 — Punctuality has no data. There is no arrival timestamp anywhere in the schema.**
The brief's §3.3 is defined as `Δt = ActualArrival − ScheduledArrival`.
`pandit_assignments` records `assigned_at`, `confirmed_at`, `declined_at` — and **no arrival**
(migration `…:20-22`; column list above). A repo-wide grep for
`arrived_at|arrival_time|check.?in|actual_arrival` returns nothing in `app/`.
`bookings.scheduled_at` is the only scheduled anchor and it is **nullable**
(`…000001…:22`), so `Δt` is undefined even if arrival were recorded.
Consequence: punctuality scoring (§3.3), the SEV-2/SEV-1 delay classification (§7.1), Scenario A's
whole timeline ("09:45 readiness check … 10:10 no arrival confirmation"), and the objective half of
the brief's §3.2 "punctuality = 1–5 **and objective event data**" are **not implementable as
described**. The subjective half (devotee's 1–5) is implementable today. §7 therefore starts by
designing the evidence producer, because a scoring algorithm over a NULL column is how a platform
ends up ranking people on nothing.

**TS-02 — `NO_SHOW` is a state that nothing can ever enter.**
`BookingLifecycle::STATUS_NO_SHOW` exists (`:35`), the transition map permits
`ASSIGNED → NO_SHOW` (`:43`), it is terminal (`:50`), and it appears in the cancellation status set
(`:73`) — **and a repo-wide grep finds zero writers outside that one definition file.** It is a
declared state with no producer. So the brief's highest-value automated flow (§7.1 "Pandit no-show →
High priority", Scenario A, §11's `pandit.arrival.missed` → Incident row) has no triggering fact in
the system today. **This is the fifth documented instance of the pattern named in
`docs/onboarding/SPEC.md` §1.3** — declared control, no enforcement — and here the control is a
religious service that silently never happened being recorded as though it had been decided.

**TS-03 — A support case has two states and no recorded resolution.**
`SupportTicket` defines exactly `OPEN` and `CLOSED` (`:31,33`); `close()` is
`forceFill(['status' => CLOSED])->save()` (`StaffSupportController.php:181`) with **no outcome field,
no reason, no remedy, no resolution record, no actor audit beyond the generic middleware**. The
brief's §4.3 prohibition — "a case should not be closed just because a support agent has sent a
message; there must be a recorded resolution outcome" — describes this code exactly. `reply()` on a
closed ticket flips it back to `OPEN` (`:129-131`) which is an implicit reopen with no reopen reason
or history table. §13's state machine (`ACKNOWLEDGED → CLASSIFIED → … → RESOLUTION_PROPOSED →
RESOLVED → CLOSED`) plus the additional states (`REOPENED`, `PENDING_THIRD_PARTY`, `REFUND_PENDING`,
`REFUND_COMPLETED`, `WITHDRAWN`, `REFERRED_EXTERNALLY`) are unrepresentable.
Note the column too: `status varchar(16)` cannot hold `RESOLUTION_PROPOSED` (19) or
`WAITING_FOR_CUSTOMER` (20). This is the identical truncation trap as Communications C-09 — the same
mistake, independently, in two modules — so §13.1 treats "widen or normalise the state column" as a
standing migration rule, not a per-task fix.

**TS-04 — There is no category that can hold a safety report.**
`SupportTicket::CATEGORIES = ['billing','booking','account','other']` (`:38`). Of the brief's §4.1
fourteen categories, the available ones are billing-ish, booking-ish, and **`other`**. A harassment
report, a privacy violation (unauthorised photo/video of a ceremony), a temple-access denial, a
prasad delivery failure, a streaming failure, an additional-charges-during-ceremony complaint
(Scenario D), or a provider-conduct complaint all land in `other` — with no priority, no restricted
queue, no independent reviewer, no escalation clock, and **no way to find them as a class**. Scenario
E in the brief ("serious safety allegation … restricted safety queue") is the single workflow where
`other` is not an acceptable value. §14 makes category taxonomy a hard prerequisite of the safety
path, and §22 G-SAFE-1 gates the whole release on it.

**TS-05 — Aggregation is a naive mean, in PHP and in TS, independently.**
`RecomputePanditRatingsCommand` computes a plain average over collected ratings
(`:64-70`, `array_column($reviews,'rating')` then mean); `packages/feedback/src/quality/quality.ts:16-21`
does `sum/count` rounded to 1 dp. **None** of the brief's §3.5 protections exist: no Bayesian prior,
no category-specific prior, no confidence/publish thresholds, no Wilson lower bound for binary
metrics, no time decay, no lifetime-vs-recent split, no sample size displayed next to the score. The
brief's own example is therefore live behaviour: Pandit A with 5.0 from 2 bookings outranks Pandit C
with 4.6 from 1,500.
This is worse here than a cosmetic unfairness, because that number is **a search ranking input**
(`card_json.rating` → `PanditSearchProvider` → Search spec §1.1), and the search side also has a
dead popularity signal (Search D-01). So the reputation quantity that decides who gets booked is
(a) computed from a tiny-sample mean, (b) multiplied into ranking, and (c) paired with a
`verification_status` flag that Onboarding TS/T-01 showed is a hand-set dropdown with no evidence.
The brief's §26.2 "average-rating overreliance" is not a hypothetical risk here; it is the current
state, and it compounds across three modules.

**TS-06 — Reviews have no lifecycle at all: no moderation, no publication, no appeal, no response.**
`booking_reviews` columns are `rating`, `comment`, timestamps — nothing else. There is no
`moderation_status`, no `publication_state`, no `redacted_version`, no `aggregation_eligibility`, no
`rating_version`, no provider-response table, no appeal table. The brief's §3.6 three separate
decisions (eligible to publish / text complies / reliable enough to count) collapse into "text is
public the moment it is submitted," if the controller exposes it publicly. Consequences: personal
data in a public review cannot be redacted without editing the original (the brief's §3.6 example);
a genuine review that should not count toward a *technical recitation* metric can't be excluded; a
pandit has **no right of response**, which §19.3 and BIS IS 19000 both treat as a fairness
requirement; and a moderation decision cannot be appealed because moderation doesn't exist.
§5.3 keeps original / moderated / aggregate-eligible as three separate records, as the brief requires.

**TS-07 — The feedback invitation and eligibility layer does not exist.**
No `feedback_requests` equivalent, no review window, no invitation producer. Per
`docs/communications/SPEC.md` C-14, `booking.completed` **does** fire (via `BookingLifecycleNotifier`)
— so the trigger exists but nothing consumes it to invite feedback. Combined with TS-06, the current
flow is "a motivated user finds a form," which systematically biases the sample toward strong
opinions in both directions. The brief's §3.1 window, §16 Scenario C/D handling, and any
"verified review rate" metric (§17.1) all require the invitation layer. §5.1 builds it, and §20
requires it before any aggregate is published — a score computed from self-selected submissions
should be labelled as such.

**TS-08 — Rating attribution trusts the client's view of who to rate.**
`RecomputePanditRatingsCommand` correctly derives a pandit's reviews from
`PanditAssignment.booking_id` (`:52-56`) — the aggregation is server-derived. But the submission path
has `booking_id` + `user_id` only, with **no check that the reviewer was a party to that booking, no
link to the assignment that served it, and no revision binding** — and there is no `subject_type` at
all, so temple and vendor service quality have no rating home (despite
`packages/feedback/src/quality/quality.ts:23-26` declaring `subject.type` as
`partner|service|fulfillment` — a TS contract with no PHP counterpart, the D-13 duplication pattern
again). The brief's §14.1 rule "the server derives the actual service provider from the booking; do
not trust a frontend-provided `pandit_id`" is honoured in the aggregate and **unelected in the
submit**. §5.2 makes reviewer-party and assignment-derived subject mandatory at write time.

**TS-09 — Zero review-integrity detection.**
No anomaly table, no duplicate-text check, no burst detection, no self-review or
provider-associated check, no retaliation pattern check. The brief's §3.5 Algorithm 5 list is entirely
absent, and §16 Scenario G ("review manipulation … do not automatically remove negative reviews merely
because timing is unusual") has no substrate. Also unguarded: nothing prevents reviews written against
bookings that never completed — `booking_reviews` has no completion precondition, so a review can
exist for a cancelled booking, which then feeds the mean (TS-05) and the search rank.
§6.5 implements the brief's rule-first posture with one property that must be explicit: **a suspicious
review is held for review, never labelled as fraud against a person** (brief §3.5), and the person
whose review is held gets a notification and an appeal path.

**TS-10 — No case reference a human can quote.**
`support_tickets` has a ULID `id` and no `case_reference`; `subject` is free text (190). The brief's
§5.2 acknowledgement flow ("generate a unique complaint number … return acknowledgement … give the
customer the complaint copy") needs a stable, tenant-unique, human-scale reference and a captured
complaint record. Nothing exists, so a devotee quoting a ticket to a grievance officer or to NCH has
nothing to quote. §9.1 adds a reference with the same `UNIQUE(tenant_id, case_reference)` the brief
specifies, and §10.2 treats the complaint copy as an artefact, not a screen.

**TS-11 — No SLA, no assignment, no escalation, and no statutory clock.**
`support_tickets` has no `assigned_to`, no `priority`, no `acknowledged_at`, no `due_at`, no
`sla_policy_version`. So neither the brief's internal targets (§5.1: safety in 5 minutes, same-day
disruption in 15) nor the statutory 48-hour acknowledge / one-month redress (Rule 4, **(unverified
here)**) can be measured, alerted on, or evidenced. The repo contains **no concept of a grievance
officer at all** (grep, §0 note). This is the gap with a calendar attached: the brief says the 2026
amendment's complaint-copy and NCH-partnership requirements take effect **2027-01-01 — 52 days from
today**. Whatever counsel concludes about scope, the engineering artefacts (recorded complaint, copy
to complainant, external referral registry, dated timers) cannot be built in a sprint, so §10 treats
them as dated milestones with a hard sequencing claim.

**TS-12 — The incident domain does not exist in Laravel, and the TS model is shaped for outages,
not ceremonies.**
No `incidents` table, no severity classification, no affected-booking linkage, no correlation, no
replacement workflow, no postmortem — confirmed by the file inventory in §1.1.
`@navdhya/incident` is the closest asset and is **SRE-flavoured**: `SEV_1..SEV_4` with **no SEV-0
safety tier**, `CommandRole` of `COMMANDER|TECHNICAL_LEAD|COMMUNICATIONS_LEAD|RECORDER` — missing the
brief's §7.4 `Operations Lead`, `Provider Liaison`, `Safety Lead`, `Finance Liaison` — and no
`affected_bookings` concept, no `replacement_candidate`, no venue-access type, no parent/child graph.
Its `PublicStatusPage` states (`INVESTIGATING|IDENTIFIED|MONITORING|RESOLVED`) are designed for a
platform status page, not for telling one family that their pandit is 40 minutes out. §12 extends
this model deliberately rather than starting over — the alert-fingerprint dedup and postmortem/corrective-action
shapes are exactly what §10.1/§12 need — while recording that a public status page must not be
reused as a devotee-facing incident notice.

**TS-13 — Replacement eligibility would be graded on unverified inputs.**
The brief's §8.2 hard gates require "active, approved onboarding", "valid relevant Pandit
qualification", "no active suspension". Cross-referenced findings say those inputs do not exist as
evidence: Onboarding T-01 (verification_status is a hand-set flag with no claims behind it) and
T-19 (no qualification or certificate model at all); no suspension or service-restriction field on
`PanditProfile` (§1.1 column list, Onboarding spec §1.1). "Available at the required time" is the one
gate with real data — `pandit_availability` and `pandit_blackouts` exist (Pandit module migrations
`…123555`, `…000002`) — and travel feasibility has no distance data, since pandit locality is itself
unresolved (Onboarding D-03-equivalent: `PanditSearchProvider` sets `city => null`). **§13's honest
conclusion: the replacement engine can rank on availability and specialisation today, and must not
claim qualification verification until Onboarding E-TR-03 lands.** §23 R-02 makes that explicit,
because an "eligible" badge computed from a dropdown is how a wrongly-qualified pandit gets sent to a
wedging ceremony.

**TS-14 — The refund boundary is respected, but only because the support module is too thin to
cross it.**
`CancelBookingUseCase` handles cancellation and the refund-adjacent state machine inside `Booking`
with lock/guard/audit/outbox (`:66-176`), and `SupportTicket` never touches money — which is the
correct separation the brief's §2.1 demands. But there is **no `support_refund_requests` object and
no request→approval→execution→confirmation chain**, so today a support agent cannot even *ask*
Finance for a refund through a tracked path; the resolution of a money complaint is presumably a
phone call and a closed ticket (TS-03). §11 builds the request object on the existing pattern rather
than a new ledger, and keeps the brief's rule: **support must not alter the payment ledger.**

**TS-15 — No privacy model for evidence, and no retention story at all.**
`support_messages` has no attachment/privacy column visible in the schema; nothing addresses the
brief's §19.1 (purpose-limited evidence, restricted safety-case access, redaction, audited evidence
access, secure deletion). Contrast `docs/communications/SPEC.md` C-15: evidence bytes and the secret
scanner are both weak there. A safety-case attachment (a photo, a screenshot of a threat) today would
be filed like a billing note, readable by whoever can read tickets. §14.3 makes safety-case
compartmentalisation a structural property, not a permission flag someone remembers to set.

**TS-16 — No closed loop between the three domains.**
The brief's closing claim — feedback detects → support resolves → incident prevents recurrence —
requires references between records that do not exist. A review has no link to a case; a ticket has
no link to a booking even (no `booking_id` column on `support_tickets`, §1.1); nothing links a
recurring complaint to a provider improvement plan. §8.3 and §12.5 build the loop as explicit
foreign references with the ownership table in §4 enforced by tests, because without links the "trust
platform" is three unrelated inboxes.

### 1.3 The pattern, now across five modules

`docs/onboarding/SPEC.md` §1.3 named "declaration without enforcement" as this codebase's recurring
integrity failure. This module adds three examples in one pass, including the two sharpest so far:

| Declared | Reality |
| --- | --- |
| `STATUS_NO_SHOW` in a lifecycle map with legal transitions | **no writer exists** (TS-02) |
| Punctuality score | **no arrival column exists** (TS-01) |
| 14 support categories in the brief | **4 in code, safety = `other`** (TS-04) |
| Case lifecycle with recorded resolution | two states, `close()` writes `CLOSED` (TS-03) |
| `QualitySignal.subject.type` in TS | no PHP counterpart (TS-08) |

The consequence for §21–§22 is a rule, not a style preference: **every state, category, gate and SLA
introduced here must ship with (a) a writer, (b) a test proving a real flow reaches it, and (c) where
it is a deadline, a measurable breach.** A state that only the seed file can reach is worse than no
state, because the next reader will assume it works.

### 1.4 Assumptions to prove, not inherit

| # | Assumption | Proof | Gate |
| --- | --- | --- | --- |
| A-1 | Counsel confirms whether the Consumer Protection (E-Commerce) Rules apply to Navdhya's model, and its role (marketplace / facilitator / supplier / combination) | written opinion | **G-LEG-0, blocks §10** |
| A-2 | The 2026 Amendment Rules' text, commencement (brief: 2027-01-01) and exact obligations are confirmed against the official notification | counsel + official gazette check | G-LEG-1 |
| A-3 | The brief's Rule 4 timings (48 h acknowledge, one month redress) match the operative text | same | G-LEG-1 |
| A-4 | An NCH convergence participation route is available and its operating terms are known; **no public API is assumed** | registration + documented workflow | G-NCH-0 |
| A-5 | BIS IS 19000:2022 principles can be self-implemented without certification, and that distinction is stated accurately in public copy | review of the standard + copy review | G-REV-1 |
| A-6 | Arrival evidence is obtainable operationally (pandit app action, coordinator, or venue check-in) | **operational decision, currently undecided** | **G-EVT-1, blocks §7 and the incident engine** |
| A-7 | A qualified recitation assessor pool exists, per tradition/shakha, willing to review consented recordings | named reviewers | G-EXP-1 |
| A-8 | Replacement candidates can be found within the same-day window at pilot scale | measured on real disruptions | G-REC-1 |

A-6 is the one that decides whether this spec's most-cited features are real. A-1…A-3 are legal and
cannot be resolved by code, so §10 ships *mechanisms* (recorded complaint, dated timers, complaint
copy, referral registry) whose thresholds come from config the officer approves — never from a
constant an engineer chose.

---

## 2. Scope and posture

### 2.1 In scope
1. Extracting reviews from `Booking` and tickets from `Customers` into owned modules, with tests
   moved intact (§3), and `packages/incident` **generalised, not rewritten** (§12).
2. **The arrival-evidence producer**, because everything quality-side depends on it (TS-01/A-6).
3. Feedback: invitation + eligibility, multi-dimensional ratings, moderation/publication/appeal/
   response as separate records, integrity rules, statistically honest aggregation.
4. Support: case model with real states, category taxonomy incl. safety, evidence, resolution
   outcomes, independent appeals, refund **requests**.
5. Grievance compliance **mechanisms**: recorded complaint, complaint copy artefact, dated statutory
   clocks, external referral registry, officer-facing queue (thresholds from approved config).
6. Incidents: severity incl. a safety tier, ownership roles, impact graph, parent/child correlation
   with no auto-merge, recovery workflows, postmortems and prevention tasks.
7. Replacement engine with **honest** gates (TS-13) and the brief's protections.
8. Safety-case compartmentalisation, 112 signposting, no-mediation rule (§14).
9. Dark-pattern prohibitions as testable interface rules (§19.2), review transparency disclosures,
   provider improvement plans.
10. Cross-module references that make the feedback→support→incident loop real (TS-16).

### 2.2 Out of scope (with owner)

| Excluded | Why | Owner |
| --- | --- | --- |
| Determining whether Navdhya is a "covered e-commerce entity" | legal | counsel, A-1 |
| Executing refunds or touching the payment ledger | §2.1 ownership table; financial authority | `Payment`/`Finance` |
| Suspending or restricting a provider | a **decision**, not a scoring side-effect | `Onboarding` claims + human review |
| Inventing or calling an NCH API | brief is right that none should be assumed | documented partner/manual route |
| Automated speech recognition of Vedic recitation | the brief's own prohibition; correctness claims would be unsupportable | never as a quality verdict |
| Recording ceremonies by default | consent, privacy, devotional sensitivity | §13.4 opt-in only |
| Predictive/ML provider-fraud labelling | explainability + harm asymmetry | later, gated, explainable rules only |
| Emergency services | this platform is not one | 112, §14.2 |
| A second notification system | `docs/communications/SPEC.md` | that module |
| Rebuilding verification claims | `docs/onboarding/SPEC.md` | that module |

### 2.3 No-external-infrastructure posture — unchanged and verified compatible
Relational DB, Laravel database queue, scheduler, existing private storage, existing
Communication/Notification modules, native audit tables. Nothing here needs a broker or a search
service. Incident evidence goes to the same access-controlled private storage that
`docs/communications/SPEC.md` §5 and `docs/onboarding/SPEC.md` §5 already require — and inherits
their unresolved gap: **the retrieval path for a stored evidence document does not exist yet**, so
§15's evidence viewer is blocked on Onboarding E-TR-01.01. That dependency is stated rather than
assumed away, because a case system whose attachments cannot be opened reproduces TS-03 exactly.

---

## 3. Architecture decision

**Today:** reviews live in `Booking`, tickets in `Customers`, incidents nowhere, and the three TS
packages describe domains with no PHP home.

**Options considered.**
- *Option B — leave reviews in `Booking` and add fields there.* `BookingReview` is already
  booking-keyed, so this looks natural. But moderation, appeals, provider responses, integrity
  signals and aggregation are not booking concerns, and `Feedback` would then be owned by the module
  whose data it audits — the reviewer and the reviewed share a deployment. It also repeats the
  Onboarding T-17 / Communications §3 extraction mistake.
- **Option A (recommended): three modules, one shared trust kernel, `packages/incident` generalised.**

```text
app/Domain/Trust/                    shared primitives, no business ownership
  References/   CaseReference, IncidentReference, ReviewReference, EvidenceReference,
                AuditRecord (actor/type/action/previous/new/reason/policy_version/correlation_id)
  Subjects/     TrustSubject (pandit|temple|vendor|service_instance), SubjectResolver  # server-derived
  Parties/      Party, PartyRole (devotee|provider|institution|platform), Entitlement  # who may see what
  Timeline/     TimelineEvent, time-ordered append
  Workflow/     StateMachine guard (shared; fixes TS-03/TS-12 width trap once), SLAClock
  Anomaly/      Fingerprint, BurstDetector, DuplicateTextDetector

app/Modules/Feedback/    eligibility · dimensions · moderation · publication · appeals · aggregation
app/Modules/Support/     cases · categories · evidence · resolutions · appeals · grievance · refund requests
app/Modules/Incident/    severity · ownership · impact graph · correlation · recovery · postmortem
```

Rules, each falsifiable:
1. **The ownership table (§4) is enforced by Deptrac and contract tests**, not by convention.
   `Feedback` cannot write a booking status; `Support` cannot write a payment; `Incident` cannot
   create an assignment; no module writes `verification_status`.
2. `packages/incident`'s existing types are **extended in place** (`SEV_0`, new roles,
   `affected_bookings`, `IncidentType` additions) with a compatibility note, so the alert/SLO/status
   machinery that already works keeps working.
3. **One state-machine implementation** in `Domain/Trust/Workflow` used by all three modules — that
   is what stops `varchar(16)` truncation from recurring a third time (TS-03, Communications C-09).
4. No case, review or incident reference is ever client-supplied; server minted, tenant-unique.
5. Shared `@navdhya/trust-contracts` for identifiers/audit/evidence/pagination, with no circular
   package deps.

---

## 4. Ownership — the brief's table, with today's column added

| Information | Authoritative owner | Exists today | Notes |
| --- | --- | --- | --- |
| Booking + service completion | `Booking` | ✅ | `BookingLifecycle`, completed status |
| Arrival / presence evidence | `Booking` or Provider Operations | ❌ **nobody** | **TS-01; A-6 decision required** |
| Review + satisfaction response | `Feedback` | ⚠️ in `Booking` | extract |
| Complaint + dispute | `Support` | ⚠️ in `Customers`, 2 states | rebuild |
| Shared operational failure | `Incident` | ❌ | TS-12 |
| Refund execution | `Payment`/`Finance` | ✅ | support may only **request** (TS-14) |
| Provider verification status | `Onboarding` | ⚠️ hand-set flag | Onboarding T-01; feedback must not infer it |
| Availability + assignment | `Booking`/`Pandit` | ✅ | `pandit_availability`, `pandit_blackouts`, assignments |
| Notification delivery | `Communication`/`Notifications` | ✅ with defects | comms C-01/C-02 |
| Rating → ranking | `Search` consumes; `Feedback` computes | ⚠️ naive mean | TS-05, Search D-01 |

**§2.1's three prohibitions, converted to tests:**
- support requesting a refund is allowed; support altering the ledger is **prohibited and asserted**.
- incident coordinating a replacement is allowed; incident inventing an assignment is **prohibited**.
- feedback computing a quality score is allowed; feedback suspending a provider is **prohibited**.
Each is a negative AC, per methodology §3 ("authorization-touching tasks MUST include a negative AC").

---

## 5. Feedback engine

### 5.1 Eligibility and invitation
Build on the one correct thing shipped (`booking_id UNIQUE`).
An invitation exists only when: a real booking reference; the reviewer is a **party** to that booking
(devotee-of-record or an authorised family member — `CustomerFamilyMember` already models the
relationship); the service reached a reviewable outcome (completed, or a documented partial);
the window (default 14 days, config) is open; no existing submission for the instance;
and the correction/appeal route is disclosed.
**A no-show or cancelled service must not receive a satisfaction form** — it routes to
report-what-happened → Support/Incident (§8.3). Today nothing prevents it (TS-09) because there is no
completion precondition, which is also why the mean is contaminated.
Invitations are produced from `booking.completed` (which fires) — the consumer is new (§21 E-TS-02).

### 5.2 Submission integrity
At write time, server-side: resolve the subject from the **assignment**, never from a client field
(fixes TS-08); record `service_instance` and `booking_revision`; validate the dimension set against
the published schema; separate a numeric rating from free text; and store the
**original verbatim** with an append-only edit history (the brief's §3.6 "preserve both review and
response", and it is also what makes a moderation appeal decidable).
`share_publicly` is an explicit, separately-stored choice — publishing a review about a named
pandit is a public act about a person, and consent to that must not be bundled with submitting a
rating.

### 5.3 Three decisions, three records
Per the brief §3.6, and as the only way to make appeals meaningful:
```
feedback_submissions        immutable original + submitter + subject + instance
feedback_publications       eligible? withheld? published_at? reason_code?
feedback_moderation_actions every action, actor, policy_version, reason, appealable
feedback_aggregation_state  counts_toward: [overall|punctuality|chanting|conduct|…]
feedback_provider_responses separately moderated, cannot mutate the original
feedback_appeals            both directions: submitter and subject
```
Personal data in a review is **redacted in the published version**, never by editing the original.
A review may be publishable and simultaneously excluded from a *technical recitation* metric — the
brief's example, and the reason `counts_toward` is a set rather than a boolean.
Moderation policy adopts the brief's table, including the two rows that are easy to get wrong:
a **negative experience publishes** if otherwise valid, and **religious disagreement is not a
suppression ground**. Provider "buy-off" of negative reviews, and refunds conditional on rating
changes, are prohibited at the interface level (§19.2) and asserted by tests, not stated as policy.

---

## 6. Quality aggregation, honestly

### 6.1 Keep the raw signals separate and visible
The brief's §3.4/§3.5 display policy is the right product shape and is adopted: lifetime rating,
recent rating, verified review count, punctuality rate, category ratings — **never collapsed into one
unexplained score**. `packages/feedback`'s `RatingAggregate{count,sum,distribution}` already stores
what Bayesian and decay maths need, so this is arithmetic on an existing structure.

### 6.2 The four estimators, and what each is for
| Algorithm | Where used | Guard |
| --- | --- | --- |
| Bayesian-adjusted `(Cμ + Σr)/(C+n)` | ranking input, internal | μ per **category**, not global; C is config |
| Wilson lower bound | binary rates: on-time, no-show, complaint-free | never the only displayed figure |
| Time decay `w = e^(−λΔt)` | "recent performance" | half-life config; **lifetime record retained** |
| Confidence thresholds (0 / 1–4 / 5–19 / 20+) | publication wording | a 2-review pandit shows "New provider", not "5.0" |
The brief's caution stands and is written as an AC: these are **proposed publication thresholds, not
statistical guarantees**, and confidence intervals ship alongside. A provider page must not imply a
precision the sample cannot support — that is a truthfulness requirement, not a statistics one.

### 6.3 What must not be aggregated
Venue, weather, platform, and devotee-side failures must not contaminate a provider score (the
brief's §26.3 failure mode) — which is only separable **if incidents carry an attributed cause**
(§12.4). Today nothing records cause, so every score is currently contaminated by construction. This
is the strongest argument for building the incident engine's cause classification **before**
publishing scores, not after.

### 6.4 Search coupling, stated honestly
`card_json.rating` reaches search ranking today. Until §6.2 lands and `docs/search/SPEC.md` D-01 is
fixed, the ranking input is a naive small-sample mean multiplied with a dead popularity weight.
§21 sequences scores **before** rank changes, and requires the rank contribution to be reported
through the Search spec's `plan_json`/trace so a score's influence is observable rather than assumed.

### 6.5 Integrity detection — rules first, and never a fraud label
Adopt the brief's ten signals as explainable rules (burst on one booking, repeated accounts,
coordinated timing, identity mismatch, duplicate text, provider-associated self-review, incentivised,
retaliatory). Outcome is always `held_for_review` + human decision + appeal, **never** an automatic
fraud determination against a person. Retaliation checks run **both directions** (Scenario G: unusual
timing alone must not remove genuine reviews; and one disputed case must not spawn multiple public
reviews on one booking — the latter is already prevented by `booking_id UNIQUE`, and that constraint
stays).

---

## 7. Punctuality — build the evidence first

### 7.1 The producer decision, which is operational not technical
`Δt` needs two facts. `scheduled_at` exists but is nullable; arrival does not exist at all. Options
for arrival evidence, with what each costs:

| Source | Signal quality | Cost / risk |
| --- | --- | --- |
| Pandit marks arrival in the partner app | direct, timestamped | needs the app surface + adoption; **location not required** |
| Devotee confirms the pandit arrived | corroborating, but party-interested | usable as secondary evidence, never sole basis for an adverse score |
| Temple/coordinator confirms | good for venue-based pujas | needs a coordinator workflow |
| Geofence / GPS | strongest | **rejected as default** — tracking a worker's movement is a privacy cost they did not accept, and `docs/communications/SPEC.md` §15.2 already forbids exposing provider location by default |

**Recommendation: pandit self-attestation as primary (an `arrived_at` write, no location), devotee
or coordinator confirmation as corroboration, and `scheduled_at` made non-null for new bookings.**
Whatever is chosen, it is A-6/G-EVT-1 and must be decided before §7.2 is specified, because an
unarrived-but-marked-arrived dispute is exactly the case that needs a documented evidence hierarchy.

### 7.2 Classification, with the exclusions doing most of the work
`Δt` buckets (early / on-time / within grace / late / material delay / no-show-after-review) with
**grace configurable per service type and booking agreement**, per the brief. The exclusions matter
more than the buckets: exclude cancelled assignments, devotee-requested reschedules, approved force
majeure, invalid timestamp records, and platform-caused scheduling errors — and keep no-shows
separate from lateness. Operationally this list is the difference between a fair reliability profile
and a pandit penalised for a temple closure or a monsoon. Each exclusion is a rule with a test, and
each requires the reason to be **recorded at the time**, not reconstructed later.

### 7.3 Two measurements, never merged
A 92% objective punctuality rate and a 3-star subjective punctuality rating are different facts and
stay visibly separate (brief §3.3). The objective figure requires §7.1; the subjective one is available
today. Publishing the objective one before §7.1 exists is the TS-01 failure restated as a UI choice.

---

## 8. Chanting, recitation and the limits of a star rating

### 8.1 Two different claims
| Claim | Who may assess | Presented as |
| --- | --- | --- |
| audible / clear / pace / comprehensible | devotee | perception, averaged |
| textual conformity, svara, recitation method, tradition-specific correctness | **qualified assessor** | expert assessment, per tradition |
Never present an unverified user rating as an authoritative Vedic quality assessment (brief §3.2). The
practical implementation is a separate `counts_toward` flag (§5.3) and a separate display block —
"devotees said: clear, audible" and "assessed by a qualified reviewer: conformity X" are different
sections of a page, and conflating them would misrepresent both a devotee's experience and a
scholar's judgement.

### 8.2 Scenario C as a spec
Split the claim the way the brief does — audibility, pace, comprehensibility, promised-scope delivery,
an actual asserted textual/phonetic error, a specific Veda/shakha promised in the listing — and route
the last two to a specialist only if A-7 holds. If no qualified assessor is available for that
tradition, the honest outcome is **unresolved for the technical claim** with a recorded reason. A
satisfaction score must not decide a scholarly question, and neither must its absence.

### 8.3 Recordings, consent, and the ASR prohibition
Recordings are opt-in with participant consent per person and per purpose; default is **no
recording**. Consent comes from the shared privacy kernel (`docs/onboarding/SPEC.md` §10,
`docs/communications/SPEC.md` §14.4) — **not** a fourth consent system, per that module's rule.
Private ceremony recordings are never exposed to other customers; assessor access is scoped,
time-boxed and audited; retention follows a policy that is devotional-sensible as well as statutory
(a śrāddha recording is not a marketing asset — the same point communications §15.4 makes).
No automated speech recognition is used to establish recitation correctness. This is worth stating
plainly in the product copy too: the platform does not have, and is not building, a machine that
judges Vedic accuracy.

---

## 9. Support engine

### 9.1 Case model
`support_cases` with: minted `case_reference` (`UNIQUE(tenant_id, case_reference)`, human-scale),
category, **type** (the brief's §4.4 distinction — information request / service complaint /
financial dispute / provider dispute / institutional dispute / safety report / formal consumer
grievance — because policy and escalation differ per type), urgency, linkage to booking/payment/
subject/incident, intake channel + timestamp, `acknowledged_at`, `due_at`, `sla_policy_version`,
assignee + team, reopen count, and a **recorded resolution**.

### 9.2 State machine — one implementation, correctly sized
The brief's full lifecycle plus `REOPENED`, `PENDING_THIRD_PARTY`, `REFUND_PENDING`,
`REFUND_COMPLETED`, `WITHDRAWN`, `REFERRED_EXTERNALLY`, with legal-transition guards in
`Domain/Trust/Workflow` and a `support_case_status_history` audit table capturing
actor / previous / new / reason / policy_version / correlation_id.
Two rules with teeth:
1. **`CLOSED` is unreachable without a resolution record** (fixes TS-03 directly — today `close()`
   writes only `CLOSED`).
2. Every state has a **writer**; a state reachable only from a seed file fails review (§1.3 rule).
Also fixes TS-04 at the type level: category and type are closed enums including safety, privacy,
conduct, temple access, prasad, streaming and additional-charges, with `other` reserved for genuinely
unclassifiable input and monitored as a **taxonomy health metric** (a growing `other` bucket means the
taxonomy is wrong, and today it is the only option).

### 9.3 Evidence, minimally
Adopt the brief's evidence package and its equally important negative rule: **do not demand personal
or family documents to report a late pandit.** Per-category required-evidence profiles, purpose
limitation enforced in the form itself (fields a devotee cannot be asked for), and a
`privacy_safe_intake` path for safety reports so the person can report without uploading identity
documents they reasonably fear exposing. Evidence provenance distinguishes original artefacts from
later interpretation (§25's Evidence Provenance item, which is genuinely load-bearing for appeals).

### 9.4 Assignment and independent review
Skill- and workload-aware routing with logged, inspectable priority components — the same
falsifiability standard `docs/onboarding/SPEC.md` §12 applies. The brief's §6.2 governance split
(collect / evaluate / execute / review quality / command) is implemented as role separation with
tests, and its rule "do not allow one staff member to control the entire process for a material
financial or safety case" becomes: high-value or safety cases require a **distinct evaluator**,
mirroring Onboarding's maker-checker fix (T-06) rather than re-deriving it.

---

## 10. Consumer grievance compliance

### 10.1 What engineering can build, and what it must not decide
Buildable now, independent of legal outcome: a recorded complaint with a minted reference; a
timestamped acknowledgement with the channel captured; a **complaint-copy artefact** the complainant
receives; a dated statutory clock that is separate from internal priority; an external referral
registry; an officer-facing queue; a published officer identity block. Not engineering's to decide:
whether these duties attach, what "acknowledgement" and "redressal" legally require, and calendar
handling — A-1…A-3, approved by the compliance officer as config.

### 10.2 Dated milestone, with the honest number
The brief states the 2026 Amendment Rules are notified but not in force, effective **2027-01-01**,
adding the complaint copy and mandatory NCH convergence partnership (**unverified here** — A-2).
Today is 2026-11-10, so **52 days**. Whatever the eventual scope, the artefacts in §10.1 are the ones
that take longest to build and are cheapest to have early, so §21 puts them in E-TS-03 rather than a
compliance epic scheduled after the feature work. The brief's line is the right one: a dated
compliance milestone, not a postponable feature.

### 10.3 The clock is not a priority field
Two independent timers per formal grievance — statutory acknowledge, statutory redress — stored as
`due_at` + `sla_policy_version` and recomputed only when policy changes, never retroactively
silently. Business-hours vs calendar-month handling is a documented decision recorded in the policy
row with the officer's approval, and a test pins it. Breach produces an escalation event and an audit
record, not just a redder UI.

### 10.4 NCH
No assumed public API (brief §1C). An adapter interface (`prepareReferral`,
`recordExternalReference`, `reconcileExternalStatus`) with an authorised-partner path **and a
documented manual fallback that works today**, an external reference store, and reconciliation as a
scheduled job. Public support pages carry the escalation route and NCH contact information (§19.3) —
hiding the grievance path is itself one of the prohibited dark patterns (§19.2), so this is a
requirement, not a courtesy.

---

## 11. Refund authority

Support produces a `support_refund_request`: case, booking, amount, basis, currency, proposed type
(full/partial), approver, Finance execution reference, executed state, and the devotee's acceptance.
Execution lives in `Payment`/`Finance`; the state transition reuses the
`CancelBookingUseCase` shape (lock → guard → write → audit → outbox) rather than inventing one.
`REFUND_PENDING` / `REFUND_COMPLETED` become real case states, closing TS-14.

The brief's §5.3 constraints that are testable rather than aspirational:
- a coupon or platform credit **may not be forced** where a monetary refund is due or where the
  consumer has not freely accepted the alternative (asserted by a resolution-type validation);
- proposed resolutions are stored separately from executed refunds (two records, two authorities);
- a refund promise that never executes must be a visible breach state, not a closed case —
  §20's refund-processing-time metric and the brief's "refunds promised but not executed" failure
  mode are the same gap;
- "reasonable period" obligations come from config the officer approves, not a constant.

---

## 12. Incident engine — generalise `@navdhya/incident`

### 12.1 Severity, with the missing tier
The existing `SEV_1..SEV_4` is adopted, and **`SEV_0` is added** for imminent threat to a person's
safety, serious misconduct allegation, or a major privacy/security emergency (TS-12). This is the one
place where the existing package's shape actively misleads: a model without a safety tier will route
a harassment report as an outage. §14 is therefore not an optional add-on; the severity enum is where
it enters the system.
Severity is *internal classification*, not a statutory or emergency-services category, and §14.2's
112 signposting exists because the platform must never imply a ticket substitutes for one.

### 12.2 Lifecycle, and what "resolved" requires
The brief's chain (`DETECTED → TRIAGED → ACKNOWLEDGED → OWNER ASSIGNED → CONTAINMENT →
IMPACT ASSESSMENT → MITIGATION → COMMUNICATION → RESTORATION → POST-INCIDENT REVIEW → CLOSED`) plus
`WAITING_FOR_EXTERNAL_PARTY`, `REPLACEMENT_SEARCH`, `CUSTOMER_DECISION_PENDING`,
`RECOVERY_IN_PROGRESS`, `RESOLVED_WITH_FOLLOW_UP`, `REOPENED`.
**Closure precondition, enforced in code:** impact contained, affected bookings enumerated, recovery
actions recorded, stakeholders informed, related support cases linked, prevention follow-up assigned.
An apology is not containment (brief §7.3), and the existing package's
`PublicStatusState = RESOLVED` must not be reusable as a shortcut to incident closure — the status
page and the incident record are different objects with different preconditions.

### 12.3 Ownership roles
Extend `CommandRole` to the brief's seven: `COMMANDER`, `OPERATIONS_LEAD`, `COMMUNICATIONS_LEAD`,
`PROVIDER_LIAISON`, `SAFETY_LEAD`, `FINANCE_LIAISON`, `RECORDER`. Small teams may hold several roles;
the **assignment is still recorded per role**, which is what makes a later review possible and what
stops a single person owning a safety case end-to-end (§9.4, and Onboarding's maker-checker again).
`incident_severity_history` records every severity change with actor and reason — a severity downgrade
is a decision, and it must look like one.

### 12.4 Correlation, with the no-auto-merge rule
Reuse `packages/incident`'s `AlertFingerprint` for dedup (a genuinely good existing asset) and add
scored correlation over provider, venue, time proximity, geographic cluster (via the shared region
registry, `docs/search/SPEC.md` §5 — **not** a second locality model), shared service dependency,
and similar classification. A high score **proposes** a parent incident; a human confirms the merge.
The brief's rule is the important one, and it is easy to violate while building the clever version:
unrelated complaints must not be merged just because they name the same pandit or temple. Merging
changes what evidence is considered together, so it needs a reason and an undo.
Parent/child: one commander coordinates N disruptions; each devotee keeps an independent case,
independent options, independent outcome. `incident_impacts` binds
`(incident_id, booking_id, effect_type, attributed_cause)` — and `attributed_cause` is what §6.3 needs
to keep a venue failure out of a provider's score.

### 12.5 The closed loop, made concrete (TS-16)
Implement the brief's §11 integration table as **references and events, not shared rows**:
`review ↔ case ↔ incident ↔ booking revision`, with `incident.closed` spawning prevention tasks and
recurring-cause analysis feeding provider improvement plans (§18). The two rules that keep it honest:
a safety incident never lives inside a star-rating workflow, and a negative review never auto-creates
a punitive incident against a provider.

### 12.6 Anomaly detection
Rules first (no-show frequency, repeated access failures, cancellation clusters, double bookings,
payment failure runs, unusual unavailability, failed-stream streaks, review/complaint clusters). No ML
labelling of people from incident history (brief §10.2) — and §2.2 makes that a standing exclusion,
not a phase.

---

## 13. Replacement and service recovery

### 13.1 Hard gates, honestly marked
| Gate | Data available today | Status |
| --- | --- | --- |
| Active, not suspended | no suspension field | ❌ blocked (TS-13) |
| Valid relevant qualification | no claims model | ❌ blocked (Onboarding T-19) |
| Relevant specialisation | `specializations`, `tradition`, `gotra`, `shakha_code` | ✅ |
| Available at the required time | `pandit_availability`, `pandit_blackouts` | ✅ |
| Travel feasible | no locality/coordinate source | ❌ blocked (Onboarding D-03-equivalent) |
| Accepts the assignment | `decline_reason` exists; acceptance flow exists | ✅ |
| Ceremony-language requirement | `languages` on profile | ✅ |
So §13 ships in two stages: **availability + specialisation + acceptance now**, and qualification,
suspension, travel after the Onboarding modules land. The interim UI copy says what was checked —
a candidate marked "eligible" on a partial gate list is TS-13's failure mode with a badge on it.

### 13.2 Ranking, with the brief's protections as invariants
`Score = w₁A + w₂Q + w₃R + w₄T + w₅L + w₆C`, configurable and evaluated (R here is *verified service
reliability* — which depends on §7.1's arrival data and §6's honest aggregation, so today R is
partly unavailable; that is stated rather than quietly filled with the naive mean).
Ranking must not be stars alone; must not use protected or sensitive attributes as proxies; a
user-declared customary requirement is considered only through a documented, lawful, operationally
appropriate rule.

> **Convergence update (S165–S184 / ADR-0071):** Authoritative ceremony start/arrival timestamps are now supplied by `PanditAssignmentService::start()` (`started_at` + checklist) via the `apps/partner` Courtyard `start` action, closing the A-07 / TS-01 evidence dependency for reliability factor *R* and no-show verification.

### 13.3 Protections, each a test
No silent substitution · no replacement without devotee acceptance · no extra charge without explicit
acceptance · no lower qualification than booked scope · no ceremony-scope change without consent · no
private detail shared beyond necessity · original booking history intact · overlapping-time
substitutes deduplicated (Scenario F's "avoid assigning the same substitute to overlapping times") ·
**and if no eligible replacement exists, say so** and offer policy-appropriate reschedule/refund.
The brief's §8.3 is the part most systems quietly drop: a fast unqualified replacement is not service
recovery, and the platform must be willing to admit there is none. That is the AC I would hold this
engine to.

### 13.4 The emergency-unavailability flow, privacy-bounded
Pandit reports unavailable → validate assignment → create incident → enumerate affected bookings →
cancel the obsolete assignment → notify operations → rank candidates → per-booking options.
**The reporting pandit supplies only what is needed to record unavailability** — no diagnosis, no
medical documentation (brief §8.1), which matters twice over here: a genuine emergency is not the
moment to demand proof, and any health detail voluntarily offered must not become case evidence that
circulates. Force-majeure exclusion (§7.2) applies automatically to the punctuality record, and §18's
improvement-plan logic applies only where the cause is remediable.

---

## 14. Safety cases

### 14.1 First-class from the taxonomy up
TS-04 means safety is not currently expressible; §9.2's category/type enum plus §12.1's `SEV_0` plus a
restricted queue are what make it real. Requirements: restricted access queue (a distinct permission,
not "staff"), a **trained independent reviewer** rather than the ticket's owner, immediate safety
posture before investigation, evidence preservation, and a confidential communication path so the
affected person can respond without their thread being visible to the accused.

### 14.2 The emergency boundary
Display **112** (India's emergency number; **(unverified here)** but cheap to confirm and cheap to be
wrong about, so the copy goes through counsel/compliance review) and state plainly that a support
ticket is not an emergency service. No forced mediation, no direct confrontation
between parties in safety cases, and no pressure channel from an accused provider to a complainant.

### 14.3 Proportionality and presumption
An allegation is not a finding. Interim protective restrictions are **proportionate, time-boxed,
reasoned, reviewable, and appealable** — the brief's "for confirmed serious misconduct or safety
risks, protective restrictions take priority over an improvement programme" is the ordering rule, and
the Onboarding spec's appeals machinery (§11.4) is where the restricted person contests it. Nothing
here may be a side effect of an aggregate score (§4's negative test).

### 14.4 Privacy
Safety evidence is compartmentalised from ordinary tickets: separate storage scope, separate
permission, access audited, redaction before any provider-facing view, retention set by policy rather
than inherited from the ticket default, and no exposure of complaint history publicly or to unrelated
staff (brief §19.1). TS-15 is closed here.

---

## 15. Blocked dependency, stated explicitly
Per `docs/communications/SPEC.md` C-14 and C-05: arrival events do not exist (TS-01/A-6) and the
**consent/notification wiring is unreliable on the send side** (comms C-01/C-05). Before any journey
here claims to notify a devotee, that module's E-CM-01 must have landed. §21 sequences accordingly.

---

## 16. Data model

All on `tenant_shared` (matching `booking_reviews`, `support_tickets`, `pandit_assignments`), each
with `tenant_id` NOT NULL, and each added to the `AssertTenantIsolation` matrix per DoD.

```text
-- Feedback (extracted from booking_reviews; that table's UNIQUE(booking_id) is PRESERVED)
feedback_subjects            subject_type · subject_id · service_type · resolved_from_assignment
feedback_invitations         booking_id · invitee · window_open/close · state · channel
feedback_submissions         booking_id UNIQUE · reviewer · subject · service_instance
                             · booking_revision · original_text(immutable) · share_publicly · submitted_at
feedback_dimensions          code · scale · counts_toward[] · expert_only · active_version
feedback_dimension_values    submission_id · dimension_code · value · dimension_version
feedback_publications        submission_id · state(published|held|withheld) · reason_code · published_at
feedback_moderation_actions  submission_id · action · actor · reason · policy_version · appealable
feedback_provider_responses  submission_id · text · state · moderated_at
feedback_aggregation_state   subject · period · lifetime · recent · sample_n · bayesian · wilson
                             · prior_code · prior_version · decay_lambda · confidence_band
feedback_score_snapshots     as above, dated, immutable — the brief's §3.7 history, needed for
                             "why did my rank change" and for §20 drift alerting
feedback_anomalies           submission_id · rule_id · signal · state(held|cleared|escalated) · actor
feedback_appeals             subject_of · requester_role · grounds · outcome · independent_reviewer

-- Support
support_cases                case_reference UNIQUE per tenant · type · category · urgency · subject
                             · booking_id/payment_id/incident_id · intake_channel · acknowledged_at
                             · due_at · sla_policy_version · assignee · reopen_count
support_case_participants    role(devotee|provider|institution|platform|representative) · entitlement
support_case_messages        author · visibility_scope  # the §14.1 confidential path needs this
support_case_evidence        description · purpose · provenance · storage_reference
                             · sensitivity_class · accessed_by · retention_class
support_case_assignments     assignee · reason · team · assigned_at
support_case_status_history  actor_id · actor_type · action · previous_state · new_state · reason
                             · policy_version · correlation_id     # exactly the brief's §13.4 shape
support_case_resolutions     proposed · proposed_by · accepted_by · remedy_type · consent_captured_at
support_case_appeals         reviewer ≠ original decider (asserted)
support_refund_requests      case · booking · amount · currency · basis · approver
                             · finance_reference · executed_at · state
support_grievances           formal_flag · complaint_record_id · copy_issued_at
                             · statutory_ack_due_at · statutory_redress_due_at
                             · officer_id · officer_decision · policy_version
support_external_referrals   channel(NCH|other) · external_reference · referred_at
                             · last_reconciled_at · external_status

-- Incidents (generalising packages/incident's shapes)
incidents                    reference UNIQUE per tenant · type · severity(SEV_0..SEV_4)
                             · status · detected_at · triaged_at · contained_at
                             · restoration_at · closed_at · attributed_cause
incident_impacts             incident · booking_id · revision · effect_type · attributed_cause
                             · devotee_outcome   # the field §6.3 depends on
incident_roles               role · actor · assigned_at · released_at   # one row per ROLE
incident_timeline            time-ordered append, evidence-integrity preserving
incident_actions             action · actor · outcome · recorded_at
incident_correlations        parent · child · score · method · confirmed_by · reason · revocable
incident_replacements        incident · booking · candidate_set · eligibility_gates_passed[]
                             · devotee_decision · consent_at · new_assignment · charge_delta
incident_communications      audience · channel · summary · sent_at   # references comms deliveries
incident_postmortems         root_cause · contributing · timeline_reconstruction · author
incident_prevention_tasks    task · owner · due · state · linked_case_or_incident
incident_severity_history    from · to · actor · reason                 # downgrade is a decision

-- Trust operational
trust_sla_policies           scope(internal|statutory) · metric · duration · calendar_handling
                             · effective_from · effective_until · approved_by · version
trust_taxonomies             kind(category|type|severity) · code · label · active · policy_version
                             # a growing 'other' bucket (§9.2) is measured against this
```
**Standing migration rules learned from this module's own defects:** any status/category/severity
column is sized for its longest legal value (TS-03, comms C-09); any enum ships with a writer and a
reachability test (TS-02, §1.3); evidence is never deleted because a case closed (§9.3, brief §13.4);
and originals are immutable with derived versions stored separately (brief §3.7).

---

## 17. APIs

Envelope `{data, meta}`, existing planes, named rate limiters, OpenAPI fragments and contract tests
per DoD item 4.

**Feedback** — `POST /feedback/eligible` · `POST /feedback` · `GET /feedback/{id}` ·
`PATCH /feedback/{id}` (edit window, append-only history) · `POST /feedback/{id}/appeal` ·
`POST /feedback/{id}/response` (provider) · `GET /providers/{id}/quality` (**with sample size and
confidence band, never bare**) · `GET /providers/{id}/reviews` (publication-eligible only) ·
`GET /feedback/subject-resolution/{ref}` (the provider's own view of what was decided about them).

**Support** — `POST/GET /support/cases` · `GET /support/cases/{id}` · `/messages` · `/evidence` ·
`/reply` · `/appeal` · `GET /support/grievance-officer` (**publicly displayed**, brief §5.1) ·
`GET /support/grievances/{id}` · admin: `/assign` · `/resolve` (requires a resolution payload — the
TS-03 fix is partly an API shape) · `/escalate` · `/grievances/{id}/record-decision` ·
`/refunds/{id}/request`.

**Incident** — `POST /incidents` · `GET /incidents/{id}` · `/triage` · `/assign` · `/actions` ·
`/impacts` · `/correlate` · `/recover` · `/resolve` (precondition-checked, §12.2) · `/postmortem`.
Admin controls separately authorize severity change, closure, provider restriction, replacement
approval, financial recovery, and **safety-case access** — the brief's §14.3 list, and the one that is
currently unspeakable in this codebase (TS-04/TS-15) is safety-case access.

**Server-derived, never client-supplied:** the rating subject, the case reference, the incident
reference, the provider identity behind a booking, refund amounts, and any eligibility conclusion.
The brief's §14.1 rule ("do not trust a frontend-provided `pandit_id`") is asserted by TS-08's fix
and by a test that submits a mismatched subject and expects rejection.

---

## 18. Provider improvement, not just demotion

The brief's §25 "particularly valuable capability" is worth keeping as an explicit product commitment
because it is the cheapest fairness win in the document: a provider with a remediable pattern gets
**a plan, not just a lower rank**.

```
repeated late arrivals → verify external circumstances (attributed_cause, §12.4)
   → identify root cause → improvement plan (availability · scheduling guidance
   · coordination · follow-up) → measure subsequent performance
```
Requirements that make it real rather than decorative: the provider sees the evidence basis and the
specific incidents (not raw complaint contents — §14.4), proposes input, and subsequent measurement is
visible so improvement is *creditable*. Safety and confirmed-misconduct cases bypass the programme
entirely (§14.3). This is also the correct home for the `docs/search/SPEC.md` §11 recommendation that
providers get user-visible controls: a reliability profile you can only be demoted by will be
experienced as arbitrary; one you can respond to and improve against is a relationship.

---

## 19. Privacy, fairness and interface honesty

### 19.1 Privacy controls (all testable, all brief-sourced)
Purpose-limited evidence · private case records · restricted safety access · PII redaction in
public reviews without editing originals · recording consent per person and purpose · defined
retention per class · audited evidence access · role/attribute-based review permissions · secure
deletion · **no public disclosure of complaint history** (also an Onboarding-adjacent concern: a
pandit's dispute history becoming a Google-able page is a serious harm).
Personal-data handling inherits `docs/onboarding/SPEC.md` §1.2 T-17's finding: ritual-identity
attributes are already treated as special-category for analytics export yet stored as unvalidated
free text — **this module must not create a third copy of that problem.** Case evidence referencing
gotra, birth details or family rites gets the same classification, not less.

### 19.2 Dark patterns prohibited as interface rules
The brief's list is unusually good and each item is expressible as a test:
complaint submission must not be harder than review submission · refund-request controls not hidden ·
positive review never preselected · no nudging to delete negative feedback · **refund never
conditional on a rating** · no false scarcity to push acceptance of a replacement · **no forced
store credit** over an eligible monetary remedy · grievance-officer contact never hidden.
The ranking-transparency item from the 2027 amendment is an engineering requirement too: the feedback
score's influence on provider ranking must be **disclosed and non-covert** (§6.4's trace reporting is
how), because a hidden ranking contribution and a prohibited dark pattern are the same object seen
from two directions.

### 19.3 Review transparency, published
What counts as a verified booking review · how aggregation works · whether recency is weighted and how
· which reviews are moderated · why one may be withheld · how providers respond · how consumers
appeal. Plus the honesty line the brief gets exactly right: **an internal trust score is never
presented as an objective or government-certified quality mark.** Implementing BIS IS 19000:2022
principles is not BIS certification (A-5) — and copy that blurs that distinction is the kind of
claim that turns a defensible engineering practice into a regulatory problem.

---

## 20. Instrumentation

Reuses `docs/communications/SPEC.md` §17 transport. Three scorecards, never blended:

**Feedback** — CSAT overall and by ritual category · verified review rate · invitation→submission
conversion (TS-07's number) · punctuality rate (once §7.1 exists) · no-show rate · recitation-assessment
coverage · moderation rate and **appeal success rate** (the metric that tells you whether moderation
is fair) · provider response rate · full rating distribution.
**Support** — first *meaningful* response time · acknowledgement compliance (statutory and internal,
separately) · time to recorded resolution · reopen rate · first-contact resolution · **refund
processing time** (TS-14's number) · appeal rate · **appeal reversal rate** · recurrence by
category/provider · customer effort score.
**Incident** — MTTD · MTTA · MTTR · affected bookings · replacement success rate · same-day recovery ·
repeat-incident rate · severity escalation rate · prevention-action completion · **safety-case time to
independent reviewer** (the one metric that matters most in §14).

Definitions kept distinct per the brief: *technical restoration* ≠ *customer resolution* ≠ *incident
closure* — a booking can be recovered while its complaint remains open, and conflating them is how a
dashboard reports health that users did not experience. Two anti-objectives, stated so they survive
contact with a roadmap: do not optimise for five-star averages (high ratings with poor incident
reporting is not reliability), and **do not reward agents for closing volume** — which is precisely
the failure TS-03 institutionalises.

Privacy guardrails as elsewhere: no raw complaint text in telemetry, no names, counts and references
only; case-level analytics are aggregated and access-audited.

---

## 21. Work breakdown

Estimate classes per methodology §1; scaffold task first per epic; tasks need the 9-section template
before `Approved`.

### E-TS-01 — Make the declared states real *(P0)*

| Task | Title | Est | Key AC |
| --- | --- | --- | --- |
| T-TS01-01 | A-6 decision recorded: arrival evidence source + `pandit_arrivals` (or equivalent) producer + `arrived_at` | M | AC-1 GIVEN a marked arrival WHEN read back THEN `arrived_at` is set with actor and channel. AC-2 `scheduled_at` non-null for new bookings. AC-3 no location/GPS column exists without a signed-off consent basis. |
| T-TS01-02 | `NO_SHOW` gets a writer, with evidence precondition and human confirmation | M | AC-1 GIVEN an assigned booking past the window with no arrival WHEN operations confirms no-show THEN status `NO_SHOW` + incident created + audit row. AC-2 **no automatic `NO_SHOW` from missing data alone**. AC-3 a test proves the state is reachable from a real flow, not a seeder. |
| T-TS01-03 | Support state machine in `Domain/Trust/Workflow`, correctly sized column, `support_case_status_history` | L | AC-1 `CLOSED` unreachable without a `support_case_resolutions` row. AC-2 every declared state has a writer + reachability test. AC-3 longest state name fits the column (regression vs TS-03/C-09). |
| T-TS01-04 | Category + case-type taxonomy incl. safety/privacy/conduct/access/prasad/streaming/additional-charges | M | AC: a harassment report is classifiable **and routeable** without `other`; `other` volume is a monitored taxonomy-health metric. |
| T-TS01-05 | Extract `booking_reviews` → `Feedback`, `support_tickets` → `Support`, tests moved intact | L | AC: all existing tests pass unchanged at the end of the move; Deptrac updated in the same task (DoD item 6). |

### E-TS-02 — Feedback core
T-TS02-01 dimensions + versions · T-TS02-02 invitations + eligibility + windows + **party check**
(fixes TS-08) · T-TS02-03 immutable originals + `counts_toward` + publications + moderation +
provider responses + appeals (TS-06) · T-TS02-04 PII redaction on the published copy only ·
T-TS02-05 consumer from `booking.completed` (TS-07) · T-TS02-06 **completion precondition** so
cancelled/no-show bookings route to report-what-happened instead · T-TS02-07 server-derived subject.

### E-TS-03 — Grievance mechanisms *(date-driven; A-1…A-3 config, not constants)*
T-TS03-01 minted `case_reference` + `UNIQUE(tenant_id, case_reference)` (TS-10) · T-TS03-02 recorded
complaint + acknowledgement with channel and timestamp · T-TS03-03 **complaint-copy artefact** ·
T-TS03-04 statutory clock separate from internal priority + `trust_sla_policies` with approval fields
(TS-11) · T-TS03-05 officer queue + decision record · T-TS03-06 publicly displayed officer block ·
T-TS03-07 NCH adapter **with a working manual fallback** (A-4) · T-TS03-08 breach escalation + audit.
AC (worth writing verbatim because it is the whole point): GIVEN a formal grievance WHEN the officer
does nothing THEN a breach event and escalation exist, and the case cannot close.

### E-TS-04 — Honest aggregation
T-TS04-01 Bayesian with category priors · T-TS04-02 Wilson for binary rates · T-TS04-03 time decay +
lifetime retention · T-TS04-04 confidence bands + publication thresholds · T-TS04-05
`feedback_aggregations` + snapshots · T-TS04-06 **`attributed_cause` exclusion** so venue/weather/
platform failures do not enter a provider score (§6.3) · T-TS04-07 rule-based integrity signals,
held-not-labelled (§6.5) · T-TS04-08 quality disclosure page (§19.3).
AC: a 2-review provider displays "New provider", never "5.0"; and no aggregate is published at all
before §7.1 lands, since without cause attribution the number is not yet defensible.

### E-TS-05 — Incident engine
T-TS05-01 generalise `@navdhya/incident` types (add `SEV_0`, roles, impact) with a compatibility note ·
T-TS05-02 lifecycle + closure preconditions · T-TS05-03 role assignment per role + severity history ·
T-TS05-04 `incident_impacts` + **`attributed_cause`** · T-TS05-05 correlation score with human-confirmed
parent/child (no auto-merge) · T-TS05-06 rule-based anomaly detection · T-TS05-07 postmortem +
prevention tasks · T-TS05-08 temple-access types (brief §9, incl. the never-bypass rule and the
four separated claims: temple verified ≠ representative verified ≠ booking confirmed ≠ **access
authorized for this date**).

### E-TS-06 — Service recovery
T-TS06-01 emergency unavailability report, privacy-bounded (§13.4) · T-TS06-02 affected enumeration +
assignment supersession · T-TS06-03 candidate eligibility on the **available** gates with
`eligibility_gates_passed[]` recorded (TS-13) · T-TS06-04 ranking · T-TS06-05 devotee consent capture
+ all §13.3 protections · T-TS06-06 **explicit no-eligible-replacement path** · T-TS06-07 Scenario F
overlapping-time substitute dedup · T-TS06-08 revision binding + notification handoff.

### E-TS-07 — Safety cases
T-TS07-01 restricted queue + permission, distinct from generic staff access · T-TS07-02 independent
trained reviewer assignment · T-TS07-03 112 signposting in copy and UI (reviewed, not assumed) ·
T-TS07-04 confidential communication path · T-TS07-05 evidence preservation + compartmentalised
storage (TS-15) · T-TS07-06 proportionate interim restrictions with expiry + review + appeal ·
T-TS07-07 no-mediation/no-contact rule enforced in workflow · T-TS07-08 ordinary feedback automation
**paused** where a safety incident is open (brief §11's crucial rule) · T-TS07-09 safety-case metric
(§20).

### E-TS-08 — Resolution fairness, refunds, and the loop
T-TS08-01 refund request object + Finance handoff, ledger untouched (TS-14, §11) · T-TS08-02
resolution options incl. reschedule/replacement/re-performance, stored separately from execution ·
T-TS08-03 **no forced credit** validation · T-TS08-04 evidence-weighted case dossier (supporting /
conflicting / missing / applicable policy / proposed remedy / decision-maker) · T-TS08-05 independent
review + appeals with a different decider · T-TS08-06 **anti-retaliation both directions** (§6.5,
brief §6.3) · T-TS08-07 improvement plans with provider-visible evidence basis (§18) · T-TS08-08
`review ↔ case ↔ incident` references + recurrence analysis (TS-16) · T-TS08-09 §19.2 dark-pattern
prohibitions as interface tests · T-TS08-10 admin negative-permission tests.

**Sequencing claim.** Nothing in §6, §7 or §13 is meaningful until E-TS-01 lands: an aggregation
engine over a mean of self-selected reviews with no arrival data and no cause attribution would
produce confidently unfair numbers about real people's livelihoods. The two smallest items here —
`arrived_at`, and a writer for a state that already exists on paper — are the highest-value work in
the document.

---

## 22. Acceptance gates

| Gate | Blocks | Evidence |
| --- | --- | --- |
| **G-EVID-1** | §7, §12, §13, §20 punctuality metrics | arrival evidence produced by a real flow, `scheduled_at` non-null, and `NO_SHOW` reachable with evidence + human confirmation. **Without this, punctuality is not measured, it is imagined.** |
| **G-STATE-1** | any case/incident surface | every declared state has a writer + reachability test; `CLOSED` requires a recorded resolution; no truncation. This gate exists because of TS-02/TS-03 and is the §1.3 pattern applied. |
| **G-SAFE-1** | trust-platform release claim | safety classifiable, restricted, independently reviewed, confidentially communicated, 112 shown, feedback automation pausable, restrictions appealable. A safety report landing in `other` (TS-04) fails this gate absolutely. |
| **G-REV-1** | public reviews / scores | eligibility enforced incl. completion + party check; original ≠ published ≠ counted as three records; redaction without editing; provider response; appeals both ways; BIS-principles copy distinguishes implementation from certification (A-5) |
| **G-STAT-1** | score publication | Bayesian + Wilson + decay + confidence bands + sample size shown; **cause attribution excludes venue/weather/platform failures**; no bare aggregate anywhere |
| **G-LEG-0/1** | §10 statutory surfaces | A-1 role determination; A-2/A-3 timings approved by the compliance officer as config with effective dates; a test pins calendar handling |
| **G-NCH-0** | external referral claim | documented partner or manual route with reference tracking; **no invented API**; public escalation info live |
| **G-REC-1** | replacement automation | gates recorded per candidate; eligibility claims limited to what is actually verified (TS-13); A-8 measured on real disruptions; the no-eligible-replacement path is exercised, not theoretical |
| **G-MONEY-1** | resolution features | support cannot write the ledger (negative test); refunds tracked to execution; no forced store credit |
| **G-PRIV-1** | evidence + safety storage | purpose-limited, access-audited, retention-classed, compartmentalised; no complaint history disclosed |
| **G-FAIR-1** | provider-facing surfaces | a provider can see the basis of a decision about them, respond, and appeal; no adverse action from an aggregate alone; improvement plans where remediable |
| **G-LOOP-1** | "closed-loop trust system" claim | review↔case↔incident references queryable; recurrence produces a prevention task; §12.5's two prohibitions tested |
| **G-TENANT-0** | every epic | `AssertTenantIsolation` across all §16 tables; negative AC per task |
| **G-OPS-1** | any SLA claim | the 12 stress scenarios in §22.2 pass; breach escalation fires; measured latency published, not target restated |

### 22.2 Stress scenarios (brief §22.4, adopted with one addition)
10,000 concurrent submissions · 1,000 cases during a major disruption · 500 bookings under one parent
incident · hundreds of replacement checks in one emergency · duplicate incident delivery ·
concurrent assignment by multiple reviewers · a refund webhook delayed hours · **closure attempted
while an affected booking remains unresolved** · reviewer auth failure mid critical case · a burst of
fraudulent negative reviews · many customers reporting one temple-access problem · sensitive
attachments uploaded to the wrong case.
**Addition, because this module's specific risk is people:** every one of these must be run with a
safety case in flight, and the assertion is about *the person* — that the safety path stayed
restricted, independent, and timely while the system was stressed, rather than merely that rows
survived.

### 22.3 Release train
*Alpha* = E-TS-01+02 (real states, honest feedback capture). *Beta* = E-TS-03+04 (grievance
mechanisms, statistical scores). *Incident Alpha* = E-TS-05 (severity + ownership + cause).
*Incident Beta* = E-TS-06 (recovery, honest gates). *Production* = E-TS-07+08 + §22 gates green.
**The 2027-01-01 compliance milestone cuts across this ordering** — §10's artefacts are scheduled in
E-TS-03 regardless of where the feature line sits, because they are dated rather than prioritised.

---

## 23. Risks & kill criteria

| # | Risk | Control | Kill / revert |
| --- | --- | --- | --- |
| R-01 | A dead state is introduced again (`NO_SHOW`-class, fifth occurrence) | G-STATE-1 reachability test per state | a state without a writer cannot merge |
| R-02 | Unqualified "eligible" replacements | `eligibility_gates_passed[]` per candidate; partial-gate wording (TS-13) | an eligible badge from incomplete gates ⇒ engine off, manual recovery |
| R-03 | Safety allegation mishandled | G-SAFE-1 end to end; SEV-0 tier; restricted queue; 112 | any safety case reaching a general queue ⇒ release blocked |
| R-04 | Providers damaged by unverified ratings | party + completion checks, cause exclusion, confidence bands, right of response, appeals, no auto-action from score | evidence of a livelihood impact from an unverified rating ⇒ suspend publication |
| R-05 | Devotee gamages refunds via repeated tickets | recurrence detection, policy caps, evidence-based remedy, independent review, appeal both ways | a pattern of unfounded claims ⇒ policy revision, not silent denial |
| R-06 | Statutory timers wrong (or wrongly assumed to apply) | config + officer approval + pinned test; A-1…A-3 (unverified here) | counsel uncertainty ⇒ mechanisms ship, claims stay conservative |
| R-07 | Refund promised, never executed | `support_refund_requests` lifecycle, `REFUND_PENDING/PENDING` visibility, breach escalation | a closed case with an unexecuted refund is a P1 |
| R-08 | Confidential dispute details leak to a provider | `visibility_scope` on messages, entitlement per party, evidence classes, access audit | any leak ⇒ access path disabled, reviewed |
| R-09 | Private ceremony images/videos exposed | opt-in recording, per-purpose consent, no cross-customer exposure, scoped assessor access, retention class | one exposure ⇒ recording feature off pending review |
| R-10 | Review moderation becomes suppression | publish-negative rule, religious-disagreement-not-a-ground, dual appeals, published methodology, appeal-success metric | an appeal pattern of over-removal ⇒ moderation moved to human-only |
| R-11 | Incident closed while customers affected | §12.2 closure preconditions + stress test 8 | a premature closure ⇒ reopen and postmortem, mandatory |
| R-12 | Correlation merges unrelated complaints | human-confirmed, reason, revocable, no auto-merge | an incorrect merge ⇒ correlation advisory-only |
| R-13 | Duplicate replacements at overlapping times | dedup in assignment resolution + Scenario F test | detected conflict ⇒ automated replacement disabled |
| R-14 | Scores contaminate search ranking while naive | §6.4 sequencing + rank-contribution trace | a rank change from an unstable mean ⇒ revert the contribution, keep the data |
| R-15 | Module extraction breaks `booking_reviews`' working uniqueness constraint | move with tests intact; constraint preserved explicitly | any regression ⇒ hold the move, fix in place |
| R-16 | Evidence becomes unopenable (comms/onboarding gap inherited) | blocked on Onboarding E-TR-01.01; §2.3 states it | evidence upload without retrieval ⇒ intake disabled |

**Verdict.** Strong direction, operationally demanding, and the brief's closing diagnosis is correct:
the value is the loop — feedback detects, support resolves the individual, incident prevents
recurrence. I would add one qualification the brief earns but does not say outright: **a trust system
that grades people has an obligation that a search index does not.** Every number here is someone's
reputation and income, so §22's gates are written as falsifiable tests rather than targets, and §21's
first epic is about making declared things real before measuring them.

**Sequencing claim.** Two small pieces of work: `arrived_at` with a producer, and a writer for the
`NO_SHOW` state that already has legal transitions. Between them they unblock punctuality scoring,
no-show incidents, the replacement engine, cause attribution, and honest provider profiles — and until
they exist, four of this module's five headline features are reports about data the platform does not
have.

---

## 24. ADRs required before approval

| ADR | Subject | Must record |
| --- | --- | --- |
| ADR-0059 | Trust kernel extraction and ownership boundaries | §3/§4; Deptrac + negative tests; `packages/incident` generalised not replaced |
| ADR-0060 | Arrival and presence evidence as the basis for punctuality | A-6 decision, worker-privacy limits, no-location default, `NO_SHOW` evidence precondition (TS-01/TS-02) |
| ADR-0061 | Review model: original ≠ published ≠ counted | eligibility, redaction-without-editing, right of response, appeals both ways, BIS-principles ≠ certification |
| ADR-0062 | Statistically honest aggregation | priors, Wilson, decay, confidence publication, cause attribution excluding venue/platform failures from provider scores |
| ADR-0063 | Grievance mechanisms with config-supplied statutory thresholds | A-1…A-3, complaint record/copy, separate statutory clock, NCH route without an assumed API |
| ADR-0064 | Incident model extension: SEV-0, roles, impact graph, correlation, closure preconditions | §12, no-auto-merge, severity history as a decision record |
| ADR-0065 | Safety-case handling | §14, restricted access, independence, confidentiality, 112, no mediation, proportionate reviewable restrictions |
| ADR-0066 | Resolution authority separation | §11, support requests only, Finance executes, no forced credit, no rating/refund coupling |
| ADR-0067 | Falsifiable-controls rule, restated for this domain | §1.3's five-module pattern: every state, category, gate and deadline ships with writer + reachability test + measurable breach |

Also required on merge: module manifests for the three modules (permissions incl.
`feedback:submit`, `feedback:moderate`, `feedback:appeal:decide`, `support:case:view`,
`support:case:resolve`, `support:grievance:manage` sensitive, `support:refund:request`,
`incident:create`, `incident:command`, `incident:close`, `safety:case:access` sensitive,
`provider:restriction:approve` sensitive); events incl. `feedback.invited`,
`feedback.submitted`, `feedback.held_for_review`, `support.case.acknowledged`,
`support.case.resolved`, `support.grievance.breached`, `incident.created`,
`incident.severity.changed`, `incident.recovery.completed`, `safety.case.escalated`;
`@navdhya/trust-contracts` types; OpenAPI fragments; a `docs/trust` index entry; and a
`docs/communications/SPEC.md` cross-reference update, since that document's journey table lists
arrival and no-show events as absent — E-TS-01 changes that claim.

---

## 25. Verification (this document)

1. Every `TS-` row cites a `file:line` that still resolves; the brief's legal/standards positions are
   marked **(unverified here)** and none is used as a constant — they are config pending counsel
   (A-1…A-5). The one date this document treats as fact is the *number of days* between today and
   2027-01-01, which is arithmetic, not law.
2. Every epic in §21 maps to at least one gate in §22; every gate has a test that fails when the
   enforcement is absent — the §1.3 pattern applied to this spec's own claims.
3. Every AC written into `docs/specs/tasks/` follows methodology §3 — observable, decidable,
   CI-testable, falsifiable; "appropriate", "reasonable", "user-friendly" do not appear.
4. Every task touching tenant data carries a tenancy AC; every task touching safety, evidence or
   restrictions carries a negative AC; every task introducing a state, category or severity carries a
   reachability AC.
5. No state, enum value, SLA threshold, eligibility gate or metric in this document is presented as
   existing unless §1.1 or §1.2 shows it.
6. No aggregate, score or punctuality figure is specified as publishable before its evidence and cause
   attribution exist (§6.3, G-STAT-1, G-EVID-1).
7. No module writes another's authoritative record; no support flow alters the ledger; no feedback
   figure suspends a person; no incident invents an assignment.
8. Cross-module dependencies are named with their owning epic
   (`docs/onboarding/SPEC.md` E-TR-01.01 evidence retrieval, E-TR-03 issuer claims, E-TR-06 privacy
   kernel; `docs/communications/SPEC.md` E-CM-01 truthfulness, E-CM-08 consent) rather than assumed
   to be ready.
9. Doc drift is a review blocker; a behaviour change without a change here fails DoD item 7.
