import { z } from 'zod';
import { widgetAccessTokenSchema } from '@navdhya/otp/schemas';

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

/** 6-digit TOTP/recovery code (MFA commands, unchanged by the OTP cutover). */
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
 * Login redeem. The phone factor is proven by the MSG91 widget's JWT
 * access token (verified server-side in Laravel); `totpCode` rides
 * alone on the second submit for MFA-enrolled accounts.
 */
export const loginRequestSchema = z
  .object({
    challengeId,
    token: widgetAccessTokenSchema.optional(),
    totpCode: z.string().trim().min(6).max(12).optional(),
  })
  .refine((v) => v.token !== undefined || v.totpCode !== undefined, {
    message: 'Either token or totpCode is required',
  });

/** Signup verification redeem. */
export const verifyRequestSchema = z.object({
  challengeId,
  token: widgetAccessTokenSchema,
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
