import { z } from 'zod';

/**
 * BFF schema validation — `auth/p0-contract.md` §1/§8.
 *
 * Schemas are the *transport* contract only. The backend re-validates
 * everything (hostile-BFF principle, §0); passing these schemas never
 * constitutes authorization.
 */

/** 1 MB request body cap (contract §8: oversize → 413). */
export const MAX_AUTH_BODY_BYTES = 1024 * 1024;

export function bodyWithinCap(contentLength: string | null): boolean {
  if (!contentLength) return true;
  const n = Number(contentLength);
  return !Number.isFinite(n) || n <= MAX_AUTH_BODY_BYTES;
}

/** Normalized E.164: `+` then 8–15 digits. */
export const e164 = z
  .string()
  .trim()
  .transform((v) => v.replace(/[\s\-().]/g, ''))
  .refine((v) => /^\+?[1-9]\d{7,14}$/.test(v), {
    message: 'Must be a valid E.164 phone number (8-15 digits)',
  });

/** 6-digit challenge code (transport form of `proof`, contract §2). */
export const challengeCode = z
  .string()
  .trim()
  .regex(/^\d{4,8}$/, 'Code must be 4-8 digits');

/** Unguessable challenge id (contract §1: ≥128-bit crypto-random). */
export const challengeId = z
  .string()
  .trim()
  .min(16)
  .max(128)
  .regex(/^[A-Za-z0-9_-]+$/, 'Invalid challenge id format');

const purpose = z.enum(['login', 'signup']);

export const otpRequestSchema = z.object({
  identity: e164,
  purpose,
});

export const signupRequestSchema = z.object({
  phone: e164,
  name: z.string().trim().min(1).max(120),
});

/**
 * Login redeem. Canonical field is `proof`; `code` is the temporary
 * migration alias (contract §2 — removal milestone recorded there).
 * The BFF logs which form arrived via `receivedForm()`.
 */
export const loginRequestSchema = z
  .object({
    challengeId,
    proof: z.string().trim().min(4).max(32).optional(),
    code: z.string().trim().min(4).max(32).optional(),
    totpCode: z.string().trim().min(6).max(8).optional(),
  })
  .refine((v) => v.proof !== undefined || v.code !== undefined, {
    message: 'Either proof or code is required',
  })
  .refine((v) => !(v.proof !== undefined && v.code !== undefined), {
    message: 'Send proof or code, not both',
  });

/** Signup verification redeem. */
export const verifyRequestSchema = z.object({
  challengeId,
  code: z.string().trim().min(4).max(32),
});

/** MFA — explicit commands only; `{...body}` spread is forbidden (contract §5). */
export const mfaVerifySchema = z.object({ code: challengeCode });
export const mfaConfirmSchema = z.object({ code: challengeCode });
export const mfaEnrollSchema = z.object({
  type: z.literal('totp').default('totp'),
});
export const mfaRevokeSchema = z.object({ code: challengeCode });
export const mfaRecoveryCodesSchema = z.object({
  code: challengeCode,
});

export type OtpRequest = z.infer<typeof otpRequestSchema>;
export type SignupRequest = z.infer<typeof signupRequestSchema>;
export type LoginRequest = z.infer<typeof loginRequestSchema>;
export type VerifyRequest = z.infer<typeof verifyRequestSchema>;

/** Contract §2 migration logging: which vocabulary arrived. */
export function receivedForm(body: { proof?: string; code?: string }): 'proof' | 'code' {
  return body.proof !== undefined ? 'proof' : 'code';
}

/**
 * Firebase phone login (S50 kit): single-use intent + ID-token
 * exchange. Transport validation only — Laravel re-validates every
 * field and stays authoritative.
 */
export const firebaseIntentSchema = z.object({ phone: e164 });

/** ULID intent id (CHAR(26), firebase_phone_auth_intents). Laravel
 * mints lowercase ULIDs in this kit, so match the canonical
 * state-schemas Crockford pattern case-insensitively. */
export const firebaseIntentId = z
  .string()
  .trim()
  .regex(/^[0-9A-HJKMNP-TV-Z]{26}$/i, 'Invalid intent id');

export const firebaseExchangeSchema = z.object({
  intentId: firebaseIntentId,
  // kreait verifies the signature against Google's keyset — length is
  // the transport guard (backend caps at 8192 too).
  idToken: z.string().trim().min(20).max(8192),
  totpCode: z.string().trim().min(6).max(8).optional(),
});
