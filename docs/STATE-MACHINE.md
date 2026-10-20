# State Machine & Challenge Lifecycle — Decoupled OTP Subsystem

**Status:** Active Specification  
**Scope:** Central DB Challenge Lifecycle (`otp_challenges`) & Tenant Onboarding (`partner_onboardings`)  
**Engine:** MySQL 8 (Production) / SQLite (Testing)  
**Manager:** `App\Platform\Identity\Otp\OtpChallengeManager`

---

## 1. Lifecycle State Diagram

```
                 [Issue Challenge: POST /otp or /signup]
                                    │
                                    ▼
                             ┌─────────────┐
                             │   PENDING   │
                             │ (sent by    │
                             │  provider)  │
                             └──────┬──────┘
                                    │
            ┌───────────────────────┼──────────────────────┐
            │                       │                      │
   (Resend requested:      (Client submits         (TTL expires:
    POST /otp/resend)       JWT: POST /login)       1200s)
            │                       │                      │
            ▼                       ▼                      ▼
    ┌───────────────┐       ┌───────────────┐      ┌───────────────┐
    │ RESEND GATE   │       │ VERIFICATION  │      │    EXPIRED    │
    │ compounding:  │       │ (authority    │      │  (fail-closed)│
    │ 60s→180s→540s │       │  attestation) │      └───────────────┘
    │ (max 3 resends)       └───────┬───────┘
    └───────┬───────┘               │
            │              ┌────────┴────────┐
            │              │                 │
            │         (Match OK)       (Mismatch / Error)
            │              │                 │
            │              ▼                 ▼
            │       ┌──────────────┐  ┌──────────────┐
            │       │   REDEEMED   │  │ ATTEMPTS +1  │
            │       │ (single-use  │  └──────┬───────┘
            │       │  terminal)   │         │
            │       └──────────────┘         ├──────────────┐
            │                                │              │
            │                          (attempts < 3) (attempts >= 3)
            │                                │              │
            │                                ▼              ▼
            │                          [STILL ACTIVE] ┌───────────────┐
            │                                         │   EXHAUSTED   │
            │                                         │ (row deleted/ │
            │                                         │  destroyed)   │
            │                                         └───────────────┘
            └───────────────────────────────────────────────┘
```

---

## 2. State & Policy Definitions

| State / Stage | Type | Behavior |
|---|---|---|
| `PENDING` / `SENT` | Active | Challenge row created; initial code dispatched by provider. Awaiting user verification or resend. |
| `REDEEMED` | Terminal | Token successfully attested and redeemed. Row marked `redeemed` (or deleted). Cannot be re-used. |
| `EXHAUSTED` | Terminal | 3 failed verification attempts reached. Challenge row is destroyed/deleted immediately from the DB. |
| `EXPIRED` | Terminal | Lifetime elapsed (1200s). Refuses redemption; client must issue a fresh challenge. |

---

## 3. Rate Limiting & Cooldown Engine

The challenge lifecycle is governed by three complementary tiers in `OtpChallengeManager` and `OtpDeliveryPolicy`:

### 3.1 Compounding Resend Schedule
- **Schedule:**
  - 1st resend allowed after **60s**.
  - 2nd resend allowed after **180s** (3 minutes).
  - 3rd resend allowed after **540s** (9 minutes).
- **Hard Cap:** Maximum 3 resends per challenge (`MAX_RESENDS = 3`).
- **Channels:** Granted per resend attempt (SMS channel `11`, Voice channel `4`, WhatsApp channel `12`).
- Calls made before the cooldown expires return HTTP 429 with `Retry-After`.

### 3.2 Fresh Challenge Cooldown & Cap
- When all resends are exhausted or a challenge expires, the user requests a fresh challenge.
- **Hourly Cap:** Maximum 3 fresh challenges per identifier per hour (`MAX_FRESH_PER_HOUR = 3`).
- **Cooldown:** When requesting fresh challenges rapidly, a mandatory 600s cooldown is enforced between subsequent challenge creations.
- Requests violating the fresh cap return HTTP 429 with `Retry-After: 600`.

### 3.3 Attempt Exhaustion (Backend Truth)
- **Maximum Attempts:** 3 failed verifies allowed (`max_attempts = 3`).
- On the 3rd incorrect attempt, the challenge row is immediately deleted from the database.
- The UI reflects attempts remaining: `(2 attempts remaining)` → `(1 attempts remaining)` → `Too many failed attempts. Please request a new code.`.
- **No Fabricated Locks:** There is no local client timer (e.g. the legacy 15-minute fake lock). The server-side destruction of the row is the definitive source of truth.

---

## 4. Database Schema (`otp_challenges`)

Stored in the central connection database:

```sql
CREATE TABLE otp_challenges (
    id VARCHAR(36) PRIMARY KEY,                    -- ULID
    tenant_id VARCHAR(36) NOT NULL,                -- Tenant boundary
    purpose VARCHAR(32) NOT NULL,                  -- 'login', 'signup', 'verification', 'stepup'
    identifier VARCHAR(64) NOT NULL,               -- Normalized phone (E.164) or email
    code_hash VARCHAR(255) NULL,                   -- Hashed code (if locally generated)
    provider VARCHAR(32) NOT NULL DEFAULT 'msg91', -- Verification provider
    provider_ref VARCHAR(128) NULL,                -- Provider reference/request ID
    status VARCHAR(32) NOT NULL DEFAULT 'sent',    -- 'sent', 'redeemed', 'failed'
    payload JSON NULL,                             -- Arbitrary context (signup names, scopes)
    attempts INT NOT NULL DEFAULT 0,               -- Increment on each verify attempt
    max_attempts INT NOT NULL DEFAULT 3,           -- Hard ceiling (3)
    resend_count INT NOT NULL DEFAULT 0,           -- Resends used (0..3)
    last_sent_at TIMESTAMP NULL,                   -- Timestamp of last dispatch
    expires_at TIMESTAMP NOT NULL,                 -- created_at + 1200s
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_identifier_purpose (identifier, purpose),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_expires_at (expires_at)
);
```

---

## 5. Security Invariants

1. **Identifier Attestation Binding:** The identifier returned by the provider's token verification must match the challenge's `identifier` byte-for-byte.
2. **Single-Use Guarantee:** Once redeemed, a challenge can never be redeemed again.
3. **Fail-Closed on Provider Errors:** If the third-party verification authority times out or returns a 5xx error, attempts are not consumed, but the challenge is not redeemed (`OtpProviderUnavailableException`).
