/**
 * @jest-environment node
 *
 * Firebase phone-login gate tests (S50 kit): transport schemas for the
 * intent/exchange BFF pair + the pure phone-flow helpers. The backend
 * re-validates everything (hostile-BFF principle, auth/p0-contract §0);
 * passing these schemas never constitutes authorization.
 */
import {
  firebaseIntentSchema,
  firebaseIntentId,
  firebaseExchangeSchema,
} from '@/application/auth-schemas';
import { normalizePhoneE164, friendlyFirebaseError } from '@/lib/firebase/phone';

/** 26-char Crockford-base32 ULID (firebase_phone_auth_intents key). */
const ULID = '01J9ZQ8M4T5V6W7X8Y9Z0A1B2C';

describe('firebaseIntentSchema', () => {
  it('accepts a normalized E.164 phone', () => {
    const parsed = firebaseIntentSchema.safeParse({ phone: '+919876543210' });
    expect(parsed.success).toBe(true);
    if (parsed.success) expect(parsed.data.phone).toBe('+919876543210');
  });

  it('strips spaces and dashes before validating', () => {
    const parsed = firebaseIntentSchema.safeParse({ phone: '+91 98765-43210' });
    expect(parsed.success).toBe(true);
    if (parsed.success) expect(parsed.data.phone).toBe('+919876543210');
  });

  it('rejects an empty phone', () => {
    expect(firebaseIntentSchema.safeParse({ phone: '' }).success).toBe(false);
  });

  it('rejects letters', () => {
    expect(firebaseIntentSchema.safeParse({ phone: '+91abcdefghij' }).success).toBe(false);
  });

  it('rejects a too-short number', () => {
    expect(firebaseIntentSchema.safeParse({ phone: '+9112345' }).success).toBe(false);
  });
});

describe('firebaseIntentId', () => {
  it('accepts a 26-char uppercase ULID', () => {
    expect(firebaseIntentId.safeParse(ULID).success).toBe(true);
  });

  it('accepts Laravel lowercase ULIDs (live e2e mints 01m4tkywx…)', () => {
    expect(firebaseIntentId.safeParse(ULID.toLowerCase()).success).toBe(true);
  });

  it('rejects short / non-base32 shapes', () => {
    expect(firebaseIntentId.safeParse(ULID.slice(0, 25)).success).toBe(false);
    expect(firebaseIntentId.safeParse('01J9ZQ8M4T5V6W7X8Y9Z0A1B-I').success).toBe(false);
  });
});

describe('firebaseExchangeSchema', () => {
  const valid = { intentId: ULID, idToken: 'x'.repeat(120) };

  it('accepts the intent + token shape without TOTP', () => {
    const parsed = firebaseExchangeSchema.safeParse(valid);
    expect(parsed.success).toBe(true);
    if (parsed.success) expect(parsed.data.totpCode).toBeUndefined();
  });

  it('accepts an optional TOTP code', () => {
    const parsed = firebaseExchangeSchema.safeParse({ ...valid, totpCode: '123456' });
    expect(parsed.success).toBe(true);
    if (parsed.success) expect(parsed.data.totpCode).toBe('123456');
  });

  it('rejects a missing idToken', () => {
    expect(firebaseExchangeSchema.safeParse({ intentId: ULID }).success).toBe(false);
  });

  it('rejects an oversize idToken (backend cap 8192)', () => {
    expect(firebaseExchangeSchema.safeParse({ ...valid, idToken: 'x'.repeat(8193) }).success).toBe(false);
  });

  it('rejects a malformed intent id', () => {
    expect(firebaseExchangeSchema.safeParse({ ...valid, intentId: 'not-a-ulid' }).success).toBe(false);
  });

  it('rejects a TOTP outside 6-8 chars', () => {
    expect(firebaseExchangeSchema.safeParse({ ...valid, totpCode: '12345' }).success).toBe(false);
    expect(firebaseExchangeSchema.safeParse({ ...valid, totpCode: '123456789' }).success).toBe(false);
  });
});

describe('normalizePhoneE164', () => {
  it('passes through a + number (stripping formatting)', () => {
    expect(normalizePhoneE164('+91 98765 43210')).toBe('+919876543210');
  });

  it('prefixes the house default (+91) for bare 10 digits', () => {
    expect(normalizePhoneE164('98765 43210')).toBe('+919876543210');
  });

  it('accepts a 12-digit 91-prefixed form', () => {
    expect(normalizePhoneE164('919876543210')).toBe('+919876543210');
  });

  it('is idempotent on its own output', () => {
    const once = normalizePhoneE164('9876543210');
    expect(normalizePhoneE164(once)).toBe(once);
  });
});

describe('friendlyFirebaseError', () => {
  it('maps the wrong-code SDK error', () => {
    expect(friendlyFirebaseError({ code: 'auth/invalid-verification-code' })).toBe(
      'Invalid code. Please try again.'
    );
  });

  it('maps the expired-code SDK error', () => {
    expect(friendlyFirebaseError({ code: 'auth/code-expired' })).toBe(
      'This code has expired. Request a new one.'
    );
  });

  it('collapses unknown auth/* codes without leaking internals', () => {
    expect(friendlyFirebaseError({ code: 'auth/internal-error' })).toBe(
      'Verification failed. Please try again.'
    );
  });

  it('returns null for non-SDK errors (BFF envelopes pass through)', () => {
    expect(friendlyFirebaseError({ code: 'UNAUTHENTICATED', status: 401 })).toBeNull();
    expect(friendlyFirebaseError(new Error('network down'))).toBeNull();
    expect(friendlyFirebaseError(undefined)).toBeNull();
  });
});
