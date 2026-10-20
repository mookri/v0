import { test, expect } from 'vitest';
import {
  hashPassword,
  verifyPassword,
  Pbkdf2CredentialVerifier,
  DEFAULT_PBKDF2_ITERATIONS,
} from '@navdhya/identity/src/adapters/pbkdf2-credentials';

test('hash/verify round-trips; wrong passwords and peppers fail', () => {
  const record = hashPassword('pepper', 's3cret', 1000);
  expect(record.version).toBe(1);
  expect(record.iterations).toBe(1000);
  expect(verifyPassword('pepper', 's3cret', record)).toBe(true);
  expect(verifyPassword('pepper', 'wrong', record)).toBe(false);
  expect(verifyPassword('other', 's3cret', record)).toBe(false);
  expect(verifyPassword('pepper', '', record)).toBe(false);
  const again = hashPassword('pepper', 's3cret', 1000);
  expect(again.saltHex).not.toBe(record.saltHex);
  expect(verifyPassword('pepper', 's3cret', again)).toBe(true);
  expect(() => hashPassword('', 'x')).toThrow('PASSWORD_PEPPER_MISSING');
  expect(DEFAULT_PBKDF2_ITERATIONS).toBeGreaterThanOrEqual(200_000);
});

test('verifier enrolls and checks per identity', async () => {
  const verifier = new Pbkdf2CredentialVerifier('pepper');
  verifier.enroll('u1', 'pw1', 1000);
  expect(await verifier.verify('u1', 'pw1')).toBe(true);
  expect(await verifier.verify('u1', 'nope')).toBe(false);
  expect(await verifier.verify('ghost', 'pw1')).toBe(false);
  expect(await verifier.verify('u1', null)).toBe(false);
  expect(() => new Pbkdf2CredentialVerifier('')).toThrow('PASSWORD_PEPPER_MISSING');
});
