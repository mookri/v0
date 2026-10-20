/**
 * @jest-environment node
 *
 * P0 auth gate tests — `auth/p0-contract.md`.
 *
 * Covers the BFF-side invariants that are automatable in this repo:
 *   - §3 throttle policy + Retry-After (gate: "Resend spam → 429")
 *   - §4 idempotency incl. C3 (concurrent double-submit → 1 claim)
 *   - §7 cookie policy per environment + authentication precedence
 *   - §1/§2 transport schemas (E.164, proof/code migration)
 *   - §8 body cap
 *
 * Backend-authoritative behaviors (OTP expiry, 3-attempt exhaustion,
 * C1/C2/C4 atomicity, session rotation) are enforced in Laravel and
 * gate-tested there — see auth/p0-contract.md §9. The gate is split:
 * §9a (current-system truth) vs §9b (M2–M5 security-improvement half —
 * landed 2026-10-09, re-run green).
 */
import {
  InProcessAuthRateLimiter,
  InProcessIdempotencyStore,
  requestFingerprint,
  THROTTLE,
  identityKey,
} from '@/application/auth-throttle';
import {
  otpRequestSchema,
  loginRequestSchema,
  receivedForm,
  bodyWithinCap,
  MAX_AUTH_BODY_BYTES,
} from '@/application/auth-schemas';
import {
  setSessionCookie,
  clearSessionCookie,
  extractSessionToken,
  sessionCookieName,
} from '@/server/session-cookie';

describe('contract §3 — throttle policy', () => {
  test('otp identity: 5 allowed, 6th denied with Retry-After', async () => {
    const limiter = new InProcessAuthRateLimiter();
    const key = identityKey('otp', '+919876543210');
    const { limit, windowSeconds } = THROTTLE.otpIdentity;

    for (let i = 0; i < limit; i++) {
      const decision = await limiter.consume(key, limit, windowSeconds);
      expect(decision.allowed).toBe(true);
    }

    const denied = await limiter.consume(key, limit, windowSeconds);
    expect(denied.allowed).toBe(false);
    expect(denied.retryAfter).toBeGreaterThanOrEqual(1);
  });

  test('distinct identities do not share a bucket', async () => {
    const limiter = new InProcessAuthRateLimiter();
    const { limit, windowSeconds } = THROTTLE.otpIdentity;
    await limiter.consume(identityKey('otp', '+911111111111'), limit, windowSeconds);
    await limiter.consume(identityKey('otp', '+911111111111'), limit, windowSeconds);
    await limiter.consume(identityKey('otp', '+911111111111'), limit, windowSeconds);
    await limiter.consume(identityKey('otp', '+911111111111'), limit, windowSeconds);
    await limiter.consume(identityKey('otp', '+911111111111'), limit, windowSeconds);

    const other = await limiter.consume(identityKey('otp', '+912222222222'), limit, windowSeconds);
    expect(other.allowed).toBe(true);
  });
});

describe('contract §4 — idempotency', () => {
  test('C3: concurrent double-submit yields exactly one claim', async () => {
    const store = new InProcessIdempotencyStore();
    const fp = await requestFingerprint({ phone: '+919876543210', name: 'A' });

    const results = await Promise.all([
      store.claim('k1', fp),
      store.claim('k1', fp),
      store.claim('k1', fp),
    ]);

    expect(results.filter((r) => r === 'claimed')).toHaveLength(1);
    expect(results.filter((r) => r === 'in-progress')).toHaveLength(2);
  });

  test('same key + completed + same fingerprint → replay', async () => {
    const store = new InProcessIdempotencyStore();
    const fp = await requestFingerprint({ a: 1 });
    expect(await store.claim('k2', fp)).toBe('claimed');
    await store.complete('k2', { challengeId: 'ch_1' });
    expect(await store.claim('k2', fp)).toBe('completed');
    expect(store.getResult('k2')).toEqual({ challengeId: 'ch_1' });
  });

  test('same key + different fingerprint → conflict (409, I13)', async () => {
    const store = new InProcessIdempotencyStore();
    const fpA = await requestFingerprint({ a: 1 });
    const fpB = await requestFingerprint({ a: 2 });
    expect(await store.claim('k3', fpA)).toBe('claimed');
    await store.complete('k3');
    expect(await store.claim('k3', fpB)).toBe('conflict');
  });

  test('requestFingerprint is deterministic and content-sensitive', async () => {
    const a1 = await requestFingerprint({ x: '1', y: [1, 2] });
    const a2 = await requestFingerprint({ x: '1', y: [1, 2] });
    const b = await requestFingerprint({ x: '2', y: [1, 2] });
    expect(a1).toBe(a2);
    expect(a1).not.toBe(b);
    expect(a1).toMatch(/^[0-9a-f]{64}$/);
  });
});

describe('contract §7 — cookie policy', () => {
  const savedEnv = { ...process.env };

  afterEach(() => {
    process.env.NODE_ENV = savedEnv.NODE_ENV;
    if (savedEnv.SESSION_COOKIE_SECURE === undefined) delete process.env.SESSION_COOKIE_SECURE;
    else process.env.SESSION_COOKIE_SECURE = savedEnv.SESSION_COOKIE_SECURE;
  });

  test('production: __Host- prefix + Secure + HttpOnly + Path=/ + SameSite=Lax', () => {
    process.env.SESSION_COOKIE_SECURE = '1';
    expect(sessionCookieName()).toBe('__Host-navdhya-session');

    const cookie = setSessionCookie('tok', 3600);
    expect(cookie).toContain('__Host-navdhya-session=tok');
    expect(cookie).toContain('Secure');
    expect(cookie).toContain('HttpOnly');
    expect(cookie).toContain('Path=/');
    expect(cookie).toContain('SameSite=Lax');
    expect(cookie).toContain('Max-Age=3600');
  });

  test('dev HTTP: intentional separate policy — no Secure, no __Host-', () => {
    process.env.SESSION_COOKIE_SECURE = '0';
    expect(sessionCookieName()).toBe('navdhya-session');

    const cookie = setSessionCookie('tok', 3600);
    expect(cookie).toContain('navdhya-session=tok');
    expect(cookie).not.toContain('Secure');
    expect(cookie).not.toContain('__Host-');
    // Dev must still be HttpOnly + SameSite.
    expect(cookie).toContain('HttpOnly');
    expect(cookie).toContain('SameSite=Lax');
  });

  test('clearSessionCookie: Max-Age=0, same name (gate: logout clears cookie)', () => {
    process.env.SESSION_COOKIE_SECURE = '1';
    const cleared = clearSessionCookie();
    expect(cleared).toContain('__Host-navdhya-session=');
    expect(cleared).toContain('Max-Age=0');
    expect(cleared).toContain('HttpOnly');
  });
});

describe('contract §7 — authentication precedence matrix (cookie-only credential)', () => {
  function req(headers: Record<string, string>): Request {
    return new Request('http://localhost/api/mfa/status', { headers });
  }

  test('row 1: cookie wins; Authorization alongside is ignored entirely', () => {
    const extraction = extractSessionToken(
      req({ cookie: 'navdhya-session=cookie-tok', authorization: 'Bearer header-tok' }),
    );
    expect(extraction.source).toBe('cookie');
    expect(extraction.token).toBe('cookie-tok');
    // The removed legacy path had a `legacyHeaderWithCookie` anomaly flag;
    // cookie-only extraction has no header state at all.
    expect(extraction).not.toHaveProperty('legacyHeaderWithCookie');
  });

  test('row 3: header-only is never a credential — even with the removed LEGACY_HEADER_AUTH flag set', () => {
    // The env var is dead code now; setting it proves the flag buys nothing.
    process.env.LEGACY_HEADER_AUTH = '1';
    try {
      const extraction = extractSessionToken(req({ authorization: 'Bearer header-tok' }));
      expect(extraction.source).toBe('none');
      expect(extraction.token).toBeNull();
    } finally {
      delete process.env.LEGACY_HEADER_AUTH;
    }
  });

  test('row 2/4 sibling: header-only → no credential (401, no fallback)', () => {
    const extraction = extractSessionToken(req({ authorization: 'Bearer header-tok' }));
    expect(extraction.source).toBe('none');
    expect(extraction.token).toBeNull();
  });

  test('row 4: nothing → no credential', () => {
    const extraction = extractSessionToken(req({}));
    expect(extraction.source).toBe('none');
    expect(extraction.token).toBeNull();
  });

  test('cookie token is never fallen back from — a header cannot shadow it', () => {
    const extraction = extractSessionToken(
      req({ cookie: 'navdhya-session=cookie-tok', authorization: 'Bearer header-tok' }),
    );
    expect(extraction.source).toBe('cookie');
    expect(extraction.token).toBe('cookie-tok');
  });

  test('legacy cookie name still extracts (secure-name rotation window)', () => {
    process.env.SESSION_COOKIE_SECURE = '1';
    try {
      // Current name is __Host-navdhya-session; a session minted before the
      // rotation still arrives on the legacy plain name.
      expect(sessionCookieName()).toBe('__Host-navdhya-session');
      const extraction = extractSessionToken(req({ cookie: 'navdhya-session=old-tok' }));
      expect(extraction.source).toBe('cookie');
      expect(extraction.token).toBe('old-tok');
    } finally {
      delete process.env.SESSION_COOKIE_SECURE;
    }
  });
});

describe('contract §1/§2 — transport schemas', () => {
  test('E.164 normalization strips separators', () => {
    const parsed = otpRequestSchema.safeParse({ identity: '+91 98765-43210', purpose: 'login' });
    expect(parsed.success).toBe(true);
    if (parsed.success) expect(parsed.data.identity).toBe('+919876543210');
  });

  test('rejects non-E.164 identity', () => {
    expect(otpRequestSchema.safeParse({ identity: 'abc', purpose: 'login' }).success).toBe(false);
    expect(otpRequestSchema.safeParse({ identity: '123', purpose: 'login' }).success).toBe(false);
  });

  test('login: proof canonical, code deprecated alias, never both (§2)', () => {
    const proofOnly = loginRequestSchema.safeParse({ challengeId: 'a'.repeat(20), proof: '123456' });
    const codeOnly = loginRequestSchema.safeParse({ challengeId: 'a'.repeat(20), code: '123456' });
    const both = loginRequestSchema.safeParse({
      challengeId: 'a'.repeat(20),
      proof: '123456',
      code: '654321',
    });
    const neither = loginRequestSchema.safeParse({ challengeId: 'a'.repeat(20) });

    expect(proofOnly.success).toBe(true);
    expect(codeOnly.success).toBe(true);
    expect(both.success).toBe(false);
    expect(neither.success).toBe(false);

    if (proofOnly.success) expect(receivedForm(proofOnly.data)).toBe('proof');
    if (codeOnly.success) expect(receivedForm(codeOnly.data)).toBe('code');
  });

  test('rejects malformed challengeId', () => {
    const bad = loginRequestSchema.safeParse({ challengeId: 'short', proof: '123456' });
    expect(bad.success).toBe(false);
  });
});

describe('contract §8 — body cap', () => {
  test('accepts within 1 MB, rejects oversize', () => {
    expect(bodyWithinCap(null)).toBe(true);
    expect(bodyWithinCap(String(MAX_AUTH_BODY_BYTES))).toBe(true);
    expect(bodyWithinCap(String(MAX_AUTH_BODY_BYTES + 1))).toBe(false);
    expect(bodyWithinCap('not-a-number')).toBe(true); // unparsable → defer to parse step
  });
});
