# Security Baseline

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 13 §1–7; doc 10 §7.5; doc 17 §14.5

The security baseline is Navdhya's normative security posture: OWASP ASVS Level 2 target, encryption in transit and at rest, secrets management, the platform-operator/break-glass surface, data protection and privacy (classification, retention, export/erasure), and SOC 2 / ISO 27001 evidence readiness. HA/DR and incident response migrate to the SRE and runbook registers.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| SEC-016 | [SEC-016](#sec-016-security-posture--encryption) | ASVS L2 posture, encryption in transit/at rest |
| SEC-017 | [SEC-017](#sec-017-secrets-management--operator-surface) | Secrets, break-glass, impersonation |
| SEC-018 | [SEC-018](#sec-018-data-protection--privacy) | Classification, retention, export/erasure |
| SEC-019 | [SEC-019](#sec-019-asvs-controls--compliance-readiness) | ASVS L2 mapping, SOC2/ISO posture |

---

## SEC-016 Security posture & encryption

- id: SEC-016
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §1–2
- depends_on: CON-003
- affects: SEC-017

### Security Posture & Targets

| Dimension | Target |
|-----------|--------|
| Standard | OWASP ASVS v4 Level 2 (all applicable controls) |
| AuthN strength | MFA (TOTP) available + tenant-enforceable ([identity & token platform §1.4](./identity-and-token-platform.md)); ACR step-up for sensitive ops |
| Session/token hygiene | JWT 15-min access + rotating refresh with reuse detection ([identity & token platform §3.2](./identity-and-token-platform.md)/§4) |
| Authorization | fail-closed effective-authority intersection ([authorization & policy §5](./authorization-and-policy.md)) |
| Audit | tamper-evident hash-chained audit log, 7-year retention ([kernel services §5](../07-kernel/kernel-services.md)) |
| Data isolation | hybrid tenancy with standing leakage suite (docs 02, 14 §5) |
| Secrets | env-based v1 with vault migration path (§3) |
| Transport | TLS 1.2+ (1.3 preferred), HSTS, certificate management |
| At rest | disk/volume encryption + application-layer encryption for high-sensitivity fields (§2) |

Compliance readiness targets: **SOC 2 Type II** evidence posture from day
one (audit log, access control, change management via CI); **ISO 27001**
control mapping maintained in this doc's §7. No certification commitment in
v1 — evidence readiness.

---

### Encryption & Data Protection

### 2.1 In transit

- TLS termination at the load balancer; TLS 1.2 minimum, 1.3 preferred;
  weak cipher suites disabled; certificates via automated renewal
  (Let's Encrypt or operator CA).
- Internal traffic (app ↔ Redis ↔ MySQL) on private network; TLS to MySQL
  enabled in prod (config: `MYSQL_ATTR_SSL_CA`); Redis with
  AUTH + TLS in prod.
- HSTS (`max-age=31536000; includeSubDomains`) on all responses;
  redirect HTTP → HTTPS at the edge.
- Outbound webhooks/hooks enforce HTTPS (§[API platform §7.1](../15-integrations/integration-framework.md), [ecosystem §1.2](../18-extensions/ecosystem.md)).

### 2.2 At rest

Layered approach:

1. **Infrastructure layer** (operator responsibility, documented): encrypted
   volumes for MySQL data, Redis persistence, and object storage buckets
   (SSE-KMS or equivalent). MySQL Enterprise TDE where licensed; otherwise
   full-disk encryption is the baseline.
2. **Application-layer encryption** for high-sensitivity fields using
   Laravel's encryption (AES-256-GCM via `APP_KEY`) — columns marked
   `*_ciphertext` across the spec:
   | Data | Location |
   |------|----------|
   | Tenant dedicated-DB credentials | `tenants.db_config_ciphertext` ([tenancy & data §3](../06-tenancy/tenancy-and-data.md)) |
   | OAuth client secrets | hashed (Passport) — never encrypt-then-store plaintext |
   | IdP client secrets / SAML keys | `identity_provider_configs` ciphertext fields ([identity & token platform §9.1](./identity-and-token-platform.md)) |
   | Payment gateway credentials | `payment_accounts.gateway_config_ciphertext` ([commercial core domains §8.2](../14-domains/finance/payments.md)) |
   | Carrier credentials | `carriers.config_ciphertext` ([commerce domains §4.2](../14-domains/commerce/promotions-shipping-returns.md)) |
   | Channel connection credentials | `channel_connections.credentials_ciphertext` ([commerce domains §9.2](../15-integrations/integration-framework.md)) |
   | Employee personal data | `employees.personal_data` JSON encrypted ([enterprise ops domains §1.2](../14-domains/hr/hr.md)) |
   | Employee bank/statutory IDs | `employees.bank_details_ciphertext`, `statutory_ids_ciphertext` |
   | Bank account numbers | masked + encrypted in `bank_accounts` ([finance domains §5.2](../14-domains/finance/ar-ap-banking.md)) |
3. **PCI posture**: the platform never stores card PAN/CVV. Payment capture
   uses gateway-hosted fields / tokenization; only gateway tokens and
   provider references persist ([commercial core domains §8](../14-domains/finance/payments.md)). SAQ-A orientation documented
   for operators.
4. **File storage**: per-tenant path prefix + signed URLs ([kernel services §7](../07-kernel/kernel-services.md));
   buckets private by default; object versioning for accidental deletion
   recovery.

### 2.3 Key management

- `APP_KEY`: 32-byte random, unique per environment, stored in env
  (secret manager in prod); rotation runbook = re-encrypt application-layer
  ciphertext columns via `platform:keys-reencrypt` (batched, chunked,
  resumable; dual-read window during rotation).
- JWT signing keys (Passport RS256): rotation with 7-day JWKS overlap
  ([identity & token platform §4.4](./identity-and-token-platform.md)).
- KMS hierarchy (vault path, §3): master key in HSM/KMS → envelope keys per
  environment → application data keys. v1 uses Laravel-native crypto; the
  envelope design is the documented migration target, not v1 work.

---

## SEC-017 Secrets management & operator surface

- id: SEC-017
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §3–4
- depends_on: SEC-016
- affects: CTRL-020

### Secrets Management

### 3.1 v1 model (normative)

- Secrets enter the process exclusively via **environment variables**
  (`.env` file in dev, secret-manager injection in prod).
- `.env` is never committed; `.env.example` documents keys with
  placeholders only.
- Secret inventory (canonical list; adding a secret requires updating this
  table — CI check scans config for undeclared env secrets):
  | Env key | Purpose |
  |---------|---------|
  | `APP_KEY` | application crypto |  | `DB_*`, `CENTRAL_DB_*`, `TENANT_DB_*` | connections |  | `REDIS_*` | cache/queue/session |  | `MAIL_*` | email provider |  | `S3_*` / `FILES_*` | object storage |  | `PASSPORT_*` | key paths/passphrase |  | `WEBHOOK_SIGNING_*` | platform webhook master config |  | `COPROVIDER_*` | copilot LLM provider credentials |  | `OPS_*` | operator surface credentials (§4) |- Secrets MUST NOT appear in: logs (redaction list, [kernel services §11.1](../07-kernel/kernel-services.md)),
  settings tables, module config files, error responses, events, or
  audit `before/after` payloads ([kernel services §5.4](../07-kernel/kernel-services.md) redaction).

### 3.2 Prohibited patterns (CI-enforced)

- Hardcoded credentials in code/config.
- `$_ENV`/`getenv` reads outside Laravel config files.
- Secrets in query strings or non-POST bodies.
- Logging of `Authorization` header values (masked as `Bearer ***`).

### 3.3 Vault migration path

Documented, not v1: secret manager (Vault/AWS SM) with app-role auth,
lease-based DB credentials, automatic rotation; application code reads
through the same Laravel config surface, so the migration is operational,
not code.

---

### Platform-Operator Surface (Break-Glass)

Cross-tenant administration is separated from tenant administration
([authorization & policy §9](./authorization-and-policy.md)). Normative design:

### 4.1 Surface & authN

- Dedicated route group `/operator` (separate Blade area + API
  `/operator-api/v1`), its own session namespace, IP allowlist
  (config), and mandatory MFA (ACR 2 always).
- Operator users: `users.is_platform_operator = true`; they hold
  **no tenant memberships**; every operator action targets a tenant
  explicitly.
- Operator permissions (kernel namespace `operator:`):
  | Permission | Action |
  |------------|--------|
  | `operator:tenant:provision` | create/suspend/offboard tenants |
  | `operator:tenant:promote` | dedicated-DB promotion runbook ([tenancy & data §7](../06-tenancy/tenancy-and-data.md)) |
  | `operator:plan:configure` | plans, rate tiers, bundles |
  | `operator:flags:ops` | ops feature flags |
  | `operator:support:impersonate` | time-boxed tenant-scoped view (§4.3) |
  | `operator:partners:review` | partner approvals ([ecosystem §7.3](../18-extensions/ecosystem.md)) |
  | `operator:marketplace:curate` / `:moderate` | listings/reviews |
  | `operator:security:alerts` | P1 security queue access |
| `operator:partners:scope_exception` | raise a tenant's sensitive-scope cap ([ecosystem §7.3](../18-extensions/ecosystem.md)); ACR 2 + dual approval |

### 4.2 Break-glass

- Emergency access without standard approval (e.g. P1 incident):
  `operator:break_glass` action with mandatory incident id + reason;
  emits `platform.operator.break_glass_used` (P1-audited, notifies all
  platform admins); grants elevated window of 4 h max.
- **Dual approval** for: tenant data export, shared-data purge, production
  flag changes, sensitive-scope exceptions ([ecosystem §7.3](../18-extensions/ecosystem.md)).

### 4.3 Support impersonation

- Time-boxed (60 min default), tenant-scoped **read-only** view with
  watermark banner; impersonation cannot mutate data, cannot see
  payroll/salary fields, and every request is logged with both operator id
  and tenant id.
- Tenant admins see impersonation sessions in their audit log
  (`actor_type = platform_operator`, [authorization & policy §9](./authorization-and-policy.md)).

### 4.4 Operator audit

- Operator actions write to BOTH the central audit stream and the target
  tenant's `audit_logs` (so tenants see what was done in their space).
- Weekly operator-activity digest to platform security channel.

---

## SEC-018 Data protection & privacy

- id: SEC-018
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §5
- depends_on: SEC-016, DATA-007
- affects: 13-data

### Data Protection (Privacy)

### 5.1 Data classification

| Class | Examples | Handling |
|-------|----------|----------|
| **Restricted** | payroll amounts, bank details, statutory IDs, payment tokens | encrypted columns; `*_sensitive` permissions; never in events/logs/exports without redaction |
| **Confidential** | employee personal data, customer PII (email/phone/address), support ticket bodies | encrypted or masked at rest; org-scoped; export audited |
| **Internal** | orders, invoices, inventory, settings | tenant isolation + RBAC |
| **Public** | storefront catalog, public tracking status | no PII |

Classification drives: redaction lists (logs/audit), event payload rules
(docs 10 §1.5, §2.5), export permissions, erasure scope.

### 5.2 Retention schedule (defaults, tenant-overridable within legal bounds)

| Data | Retention | Mechanism |
|------|-----------|-----------|
| Audit logs | 7 years | partitions → archive ([tenancy & data §10](../13-data/data-architecture.md)) |
| Financial documents (invoices, journals) | 7 years | statutory default |
| Payroll records | 7 years | statutory default |
| Outbox (published) | 30 days | purge job |
| Notification log | 90 days | purge |
| Webhook delivery log | 30 days | purge |
| Idempotency keys | 24 h | expiry |
| Carts (expired) | 7 days | purge |
| Support tickets | 3 years | archive then erase |
| Soft-deleted aggregates | 90 days | purge job |
| Copilot conversations | tenant policy (default 180 days) | purge |

### 5.3 Export & erasure (GDPR-style hooks)

- **Tenant export**: `platform:tenant-export --tenant=<id>` —
  machine-readable archive (JSON/CSV per table + files) to object storage
  with download link; operator or tenant-owner (ACR 2) initiated;
  `platform.tenant.exported` audit.
- **Tenant erasure**: offboarding workflow ([tenancy & data §10](../13-data/data-architecture.md)): export first →
  retention-hold period → purge tenant rows from shared DB, files,
  projections, search indexes; dedicated DB dropped; central rows retained
  only where legal (audit log archive, anonymized).
- **Subject (person) requests**: `platform:subject-export` /
  `:subject-erase` — per user/customer across modules: PII fields
  erased or pseudonymized; business records preserved with personal
  references replaced by `REDACTED` markers (financial integrity is
  never broken); `platform.subject.erased`.
- All three flows are workflow-managed with dual approval and full audit.

### 5.4 Cross-border

- v1 single region ([architecture master §5](../02-architecture/architecture-master.md)); data residency commitments documented per
  deployment region; no cross-region replication without operator
  re-attestation (§7 multi-region path).

---

## SEC-019 ASVS controls & compliance readiness

- id: SEC-019
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §6–7
- depends_on: SEC-016
- affects: 19-testing

### Application Security Controls (ASVS L2 mapping)

### 6.1 Input & output

- **Validation**: all input via FormRequests (strict, unknown fields
  rejected — [API platform §3.1](../07-kernel/api-platform.md)); server-side only, never trusting client checks.
- **SQL**: Eloquent/query builder with bindings exclusively; raw expressions
  require review + architecture-test allowlist; no string-concatenated SQL.
- **XSS**: Blade escaping by default; any {!! !!} usage is allowlisted and
  reviewed; stored user HTML (knowledge base, listing descriptions, [ecosystem](../18-extensions/ecosystem.md))
  sanitized with a tag allowlist.
- **CSRF**: web forms token-protected; API bearer flows exempt by design
  ([API platform §8.4](../07-kernel/api-platform.md)).
- **SSRF**: webhook/hook URL validation (private/loopback/link-local
  rejection, no redirect following — docs 06 §7.1, 11 §1.2); carrier/
  channel adapter egress restricted to provider domains.
- **File uploads**: MIME sniffing + extension allowlists, size caps,
  image dimension validation, quarantine scan hook ([kernel services §7](../07-kernel/kernel-services.md)); uploaded
  files never served from the app domain (signed URLs / separate domain).
- **Deserialization**: no untrusted unserialize; JSON only at boundaries.
- **Error handling**: kernel exception renderer maps everything to the
  error envelope ([API platform §4](../07-kernel/api-platform.md)); stack traces never leave the process in
  prod; debug mode is CI/dev-only.

### 6.2 Authentication hardening

- Argon2id password hashing; password policy: 12+ chars, breach-list check
  (k-anonymity API, no plaintext transmission), no composition theater.
- Credential stuffing defenses: per-email + per-IP throttles, lockouts
  ([identity & token platform §11](./identity-and-token-platform.md)), device/session drift re-auth ([identity & token platform §7](./identity-and-token-platform.md)).
- Timing-safe comparisons for tokens, codes, HMACs.
- Enumerability: login/invite/reset flows return uniform responses
  (no "user exists" leakage); 404s do not distinguish tenant scope misses
  ([API platform §4.2](../07-kernel/api-platform.md)).

### 6.3 Session & token hardening

- Cookies: `Secure; HttpOnly; SameSite=Lax`; session fixation
  protection (regenerate on privilege change).
- JWT: audience + issuer validation mandatory at every resource check;
  `none` algorithm rejected; clock skew tolerance ≤ 30 s.
- Refresh reuse detection ([identity & token platform §3.2](./identity-and-token-platform.md)) is a P1 security signal.

### 6.4 Logging & monitoring (security view)

- Security event stream (all emit to audit + alerting):
  auth failures, step-ups, MFA changes, token revocations, refresh reuse,
  SoD blocks/warnings, permission denials on sensitive routes, audit chain
  breaks, tenant isolation failures, hook/webhook SSRF attempts, break-glass
  usage, impersonation sessions, operator data exports.
- Alert classes per [kernel services §11.4](../07-kernel/kernel-services.md); P1 pages immediately.
- Clock sync (NTP) mandatory on all nodes (audit hash chain + token
  validation depend on it).

### 6.5 Dependency & supply-chain security

- `composer audit` + `npm audit` in CI (fail on high/critical).
- Lockfiles committed; dependency updates via PR with changelog review.
- Private package integrity: packagist/npm checksums pinned by lockfiles.
- Container/base image pinning (deployment artifact digests recorded per
  release).

---

### Compliance Readiness

### 7.1 SOC 2 evidence posture

| Trust service criterion | Platform evidence source |
|--------------------------|--------------------------|
| CC6.1 Logical access | RBAC/ABAC matrix tests ([testing strategy §6](../19-testing/testing-strategy.md)), permission registry audit trail |
| CC6.2 New user provisioning | Invite/JIT/SCIM flows + audit ([identity & token platform](./identity-and-token-platform.md)) |
| CC6.3 Access removal | Membership revocation live re-check + token revocation tests |
| CC6.6 Authentication | MFA, step-up, token platform tests ([identity & token platform §13](./identity-and-token-platform.md)) |
| CC7.2 Monitoring | Anomaly module + security event stream (§6.4) |
| CC7.3 Incident response | §9 runbooks + P1 alerting |
| CC8.1 Change management | CI gates ([testing strategy §10](../19-testing/testing-strategy.md)), migration expand/contract rule ([tenancy & data §8.3](../13-data/data-architecture.md)), release records |
| A1.2 Availability | /ready probes, HA design (§8), RPO/RTO ([architecture master §6.1](../02-architecture/architecture-master.md)) |
| C1.1 Confidentiality | Classification + retention (§5) |

Evidence exports: audit log queries + CI artifacts are the deliverables;
the audit log is the system of record for "who did what, when".

### 7.2 ISO 27001 control mapping (selected)

- A.5.15 Access control → [authorization & policy](./authorization-and-policy.md) (RBAC/ABAC/SoD).
- A.5.17 Authentication → [identity & token platform](./identity-and-token-platform.md) (MFA, federation, step-up).
- A.8.2 Privileged access → §4 operator surface + break-glass.
- A.8.5 Secure authentication → [identity & token platform §11](./identity-and-token-platform.md) brute-force controls.
- A.8.15 Logging → [kernel services §5](../07-kernel/kernel-services.md) + §6.4.
- A.8.16 Monitoring → anomaly module + alert classes.
- A.8.19 Network security → §2.1 TLS + private network segmentation.
- A.8.24 Use of cryptography → §2 encryption layers + key management.
- A.5.23–A.5.30 Incident management → §9.

### 7.3 Payment data

PCI DSS scope minimization (§2.2.3): no PAN storage; gateway-hosted
capture; quarterly ASV-scan readiness is an operator obligation documented
in the deployment runbook.

---

---

## Supplementary migrated content

### Verification requirements & assumptions (migrated)

1. **ASVS L2 checklist** executed per release (automated where possible:
   headers, cookie flags, TLS config scan, error leakage probe).
2. **Pen-test surface inventory**: public endpoints ([commerce domains §10.2](../14-domains/commerce/store-checkout.md) allowlist),
   OAuth endpoints, SCIM, webhook ingestion — each with abuse-case tests.
3. **Secrets scan**: CI pre-commit + pipeline scan for credential patterns;
   `.env` hygiene test (no real values in example files).
4. **Encryption verification**: ciphertext columns round-trip tests; key
   rotation runbook executed end-to-end in staging per release.
5. **Operator surface tests**: break-glass dual approval, impersonation
   read-only enforcement, operator audit dual-write (central + tenant).
6. **Erasure tests**: subject-erase pseudonymizes PII without breaking
   financial integrity (journal entries still balance; invoices render with
   REDACTED markers).
7. **Dependency gates**: composer/npm audit fail-on-high enforced in CI.
8. **DR drill**: quarterly restore drill produces a signed report
   (restored tenant passes smoke suite; RTO measured).

---

- The platform operator (deployment owner) is responsible for
  infrastructure-layer encryption, certificate lifecycle, and secret
  manager operations; this spec defines the application contract they must
  meet.
- No hardware security module in v1 (Laravel-native crypto + KMS envelope
  as the documented upgrade).
- Compliance certifications (SOC 2 attestation, ISO registration) are
  commercial milestones after v1 GA; v1 delivers the evidence machinery.

---

### Data sensitivity classes (feed into classification)

Data sensitivity classes declared by Phase 4 (§7.5) and Phase 4a (§14.5) are consolidated into the data-classification register of [data architecture](../13-data/data-architecture.md). They drive field-masking, encryption-at-rest decisions, and AI-surface exposure rules.
