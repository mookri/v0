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
  test('accepts a safe reference and trims', () => {
    const parsed = savedAddSchema.safeParse({ ref_id: ' puja-123 ', title: 'Ganesh Puja' });
    expect(parsed.success).toBe(true);
    if (parsed.success) expect(parsed.data.ref_id).toBe('puja-123');
  });

  test('rejects path-smuggling characters', () => {
    expect(savedAddSchema.safeParse({ ref_id: '../admin' }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_id: 'a/b' }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_id: 'a:b' }).success).toBe(false);
    expect(savedAddSchema.safeParse({ ref_id: '' }).success).toBe(false);
    expect(savedAddSchema.safeParse({}).success).toBe(false);
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
  test('normalizes phone contacts to E.164 (§1/I3 identity dedupe)', () => {
    const parsed = verificationCreateSchema.safeParse({ type: 'phone', contact: '+91 98765-43210' });
    expect(parsed.success).toBe(true);
    if (parsed.success && parsed.data.type === 'phone') {
      expect(parsed.data.contact).toBe('+919876543210');
    }
  });

  test('rejects phone contact that is not E.164', () => {
    expect(verificationCreateSchema.safeParse({ type: 'phone', contact: 'call-me' }).success).toBe(false);
  });

  test('validates email contacts for type=email', () => {
    expect(verificationCreateSchema.safeParse({ type: 'email', contact: 'a@b.co' }).success).toBe(true);
    expect(verificationCreateSchema.safeParse({ type: 'email', contact: 'not-an-email' }).success).toBe(false);
  });

  test('rejects unknown types', () => {
    expect(verificationCreateSchema.safeParse({ type: 'fax', contact: 'a@b.co' }).success).toBe(false);
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
