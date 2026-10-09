/**
 * @jest-environment node
 *
 * P1 gate tests — transport schemas for session/state routes
 * (`auth/p0-contract.md` §8) and the "no authority leak" rule (§5):
 * a schema must define exactly what a route may forward.
 */
import {
  savedAddSchema,
  settingsUpdateSchema,
  verificationCreateSchema,
  verificationConfirmSchema,
  revokeOthersSchema,
} from '@/application/state-schemas';

describe('savedAddSchema', () => {
  // Mirrors the backend wire: ref_type enum + Crockford-base32 ULID.
  const ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

  test('accepts the backend contract (ref_type + ULID) and trims', () => {
    const parsed = savedAddSchema.safeParse({ ref_type: 'puja', ref_id: ` ${ULID} ` });
    expect(parsed.success).toBe(true);
    if (parsed.success) expect(parsed.data.ref_id).toBe(ULID);
  });

  test('rejects path-smuggling characters', () => {
    expect(savedAddSchema.safeParse({ ref_type: 'puja', ref_id: '../admin' }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_type: 'puja', ref_id: 'a/b' }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_type: 'puja', ref_id: 'a:b' }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_type: 'puja', ref_id: '' }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_type: 'puja', ref_id: ULID.slice(0, 25) }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_type: 'puja', ref_id: 'puja-123' }).success).toBe(false);
    expect(savedAddSchema.safeParse({}).success).toBe(false);
  });

  test('ref_type is required and closed (backend in:puja,content,service)', () => {
    expect(savedAddSchema.safeParse({ ref_id: ULID }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_type: 'admin', ref_id: ULID }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_type: 'puja', ref_id: ULID }).success).toBe(true);
  });
});

describe('settingsUpdateSchema', () => {
  test('accepts plain objects only', () => {
    expect(settingsUpdateSchema.safeParse({ 'display.name': 'A' }).success).toBe(true);
    expect(settingsUpdateSchema.safeParse({}).success).toBe(true);
    expect(settingsUpdateSchema.safeParse([]).success).toBe(false);
    expect(settingsUpdateSchema.safeParse(null).success).toBe(false);
    expect(settingsUpdateSchema.safeParse('x').success).toBe(false);
  });
});

describe('verificationCreateSchema', () => {
  test('normalizes phone values to E.164 (§1/I3 identity dedupe)', () => {
    const parsed = verificationCreateSchema.safeParse({ channel: 'phone', value: '+91 98765-43210' });
    expect(parsed.success).toBe(true);
    if (parsed.success && parsed.data.channel === 'phone') {
      expect(parsed.data.value).toBe('+919876543210');
    }
  });

  test('rejects phone value that is not E.164', () => {
    expect(verificationCreateSchema.safeParse({ channel: 'phone', value: 'call-me' }).success).toBe(false);
  });

  test('validates email values for channel=email', () => {
    expect(verificationCreateSchema.safeParse({ channel: 'email', value: 'a@b.co' }).success).toBe(true);
    expect(verificationCreateSchema.safeParse({ channel: 'email', value: 'not-an-email' }).success).toBe(false);
  });

  test('rejects unknown channels', () => {
    expect(verificationCreateSchema.safeParse({ channel: 'fax', value: 'a@b.co' }).success).toBe(false);
  });

  test('rejects the legacy {type, contact} wire shape (M1 — backend expects channel/value)', () => {
    expect(verificationCreateSchema.safeParse({ type: 'phone', contact: '+919876543210' }).success).toBe(false);
  });
});

describe('verificationConfirmSchema', () => {
  test('id charset excludes path/segment separators (backend path interpolation)', () => {
    expect(verificationConfirmSchema.safeParse({ id: 'v_abc123XYZ', code: '123456' }).success).toBe(true);
    expect(verificationConfirmSchema.safeParse({ id: 'a/b:confirm', code: '123456' }).success).toBe(false);
    expect(verificationConfirmSchema.safeParse({ id: 'a.b', code: '123456' }).success).toBe(false);
    expect(verificationConfirmSchema.safeParse({ id: 'short', code: '123456' }).success).toBe(false);
  });

  test('code must be 4-8 digits', () => {
    expect(verificationConfirmSchema.safeParse({ id: 'v_abc123XYZ', code: '12345' }).success).toBe(true);
    expect(verificationConfirmSchema.safeParse({ id: 'v_abc123XYZ', code: '123456789' }).success).toBe(false);
    expect(verificationConfirmSchema.safeParse({ id: 'v_abc123XYZ', code: 'abcde' }).success).toBe(false);
  });
});

describe('revokeOthersSchema (bodyless command)', () => {
  test('accepts empty object; strips unknown keys so nothing extra is forwardable', () => {
    const empty = revokeOthersSchema.safeParse({});
    expect(empty.success).toBe(true);

    const withExtras = revokeOthersSchema.safeParse({ scope: 'all-devices' });
    expect(withExtras.success).toBe(true);
    if (withExtras.success) expect(withExtras.data).toEqual({});
  });
});
