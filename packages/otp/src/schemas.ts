import { z } from 'zod';

/**
 * Wire shapes of the OTP subsystem's envelopes (the `data` payload a
 * BFF proxies verbatim from Laravel). Every screen validates through
 * these, so a contract drift surfaces as a parse error at the seam,
 * never as a undefined field three renders deep.
 */

/** GET auth/otp/config — public widget bootstrap. */
export const otpConfigSchema = z.object({
  object: z.string().optional(),
  provider: z.string().optional(),
  widget_id: z.string().min(1),
  token_auth: z.string().min(1),
  otp: z.object({
    digits: z.number().int(),
    expires_in_seconds: z.number().int(),
  }),
  resend: z.object({
    max: z.number().int(),
    delays_seconds: z.array(z.number().int()),
    fresh_retry_after_seconds: z.number().int(),
    channels: z.array(z.string()),
  }),
});

export type OtpConfig = z.infer<typeof otpConfigSchema>;

/** 202 from challenge issue (otp request, signup) and login's MFA hold. */
export const otpChallengeSchema = z.object({
  object: z.string().optional(),
  challenge_id: z.string().min(1),
  expires_in: z.number().int().optional(),
  status: z.string().optional(),
  next_action: z.string().optional(),
  methods: z.array(z.string()).optional(),
});

export type OtpChallenge = z.infer<typeof otpChallengeSchema>;/** 202 from the server-gated resend endpoint. */
export const otpResendSchema = z.object({
  object: z.string().optional(),
  channel: z.string(),
  channels: z.array(z.string()),
  resends_left: z.number().int(),
  next_allowed_at: z.string().nullable(),
});

export type OtpResend = z.infer<typeof otpResendSchema>;

/** 200/201 from login + signup verification: the HMAC actor session. */
export const customerSessionSchema = z.object({
  object: z.literal('customer_session'),
  token: z.string().min(1),
  expires_at: z.string(),
  tenant_id: z.string(),
  actor: z.object({
    user_id: z.string(),
    session_id: z.string(),
  }),
});

export type CustomerSession = z.infer<typeof customerSessionSchema>;

/** The MSG91 widget hands back a JWT access token — shape-checked here. */
export const widgetAccessTokenSchema = z
  .string()
  .min(20)
  .regex(
    /^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/,
    'expected a JWT-shaped access token',
  );

/** Body for login redeem: {challengeId, token, totpCode?}. The BFF
 * injects the tenant server-side; client value is ignored. */
export const redeemBodySchema = z.object({
  tenant_id: z.string().default(''),
  challenge_id: z.string().min(1),
  token: widgetAccessTokenSchema,
  totp_code: z.string().min(6).max(12).optional(),
});

export type RedeemBody = z.infer<typeof redeemBodySchema>;

/** Second-phase login body when the TOTP arrives alone. */
export const totpBodySchema = z.object({
  tenant_id: z.string().default(''),
  challenge_id: z.string().min(1),
  totp_code: z.string().min(6).max(12),
});

export type TotpBody = z.infer<typeof totpBodySchema>;

/** Body for the contact-verification confirm endpoint. */
export const verificationConfirmBodySchema = z.object({
  token: widgetAccessTokenSchema,
});

export type VerificationConfirmBody = z.infer<typeof verificationConfirmBodySchema>;
