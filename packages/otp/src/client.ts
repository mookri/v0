import { z } from 'zod';
import {
  customerSessionSchema,
  otpChallengeSchema,
  otpConfigSchema,
  otpResendSchema,
  type CustomerSession,
  type OtpChallenge,
  type OtpConfig,
  type OtpResend,
  type RedeemBody,
  type TotpBody,
} from './schemas';

/**
 * Typed BFF client for the OTP subsystem. Apps pass their own base
 * path (pure proxy routes in front of Laravel); every response is
 * envelope-parsed and schema-validated, and every failure — HTTP,
 * network, or contract — surfaces as one typed OtpRequestError with
 * the server's Retry-After when present.
 */

export class OtpRequestError extends Error {
  constructor(
    readonly status: number,
    readonly code: string,
    message: string,
    readonly requestId = '',
    readonly retryAfterSeconds: number | null = null,
  ) {
    super(message);
    this.name = 'OtpRequestError';
  }
}

export type FetchLike = typeof fetch;

const envelopeSchema = z.object({
  data: z.unknown().optional(),
  error: z
    .object({
      code: z.string(),
      message: z.string(),
      request_id: z.string().optional(),
    })
    .optional(),
  meta: z.object({ request_id: z.string().optional() }).optional(),
});

export type OtpClientPaths = {
  config?: string;
  request?: string;
  signup?: string;
  resend?: string;
  login?: string;
  verify?: string;
  confirm?: string;
};

export interface OtpClientOptions {
  basePath: string;
  fetchImpl?: FetchLike;
  paths?: OtpClientPaths;
  getAuthToken?: () => string | null | undefined;
}

/** Redeem verdict: a session, or login's MFA phase boundary. */
export type RedeemResult =
  | { kind: 'session'; session: CustomerSession }
  | { kind: 'mfa_required'; challenge: OtpChallenge };

const joinPath = (base: string, path: string): string =>
  `${base.replace(/\/$/, '')}${path.startsWith('/') ? path : `/${path}`}`;

export function createOtpClient({ basePath, fetchImpl, paths = {}, getAuthToken }: OtpClientOptions) {
  const doFetch: FetchLike = fetchImpl ?? ((...args) => fetch(...args));

  async function call<T>(
    path: string,
    init: { method: 'GET' | 'POST'; body?: unknown },
    parse: (data: unknown) => T,
  ): Promise<T> {
    const headers: Record<string, string> = {};
    if (init.body !== undefined) {
      headers['content-type'] = 'application/json';
    }
    const auth = getAuthToken?.();
    if (auth) {
      headers.authorization = `Bearer ${auth}`;
    }

    let response: Response;
    try {
      response = await doFetch(joinPath(basePath, path), {
        method: init.method,
        headers,
        cache: 'no-store',
        credentials: 'include',
        body: init.body === undefined ? undefined : JSON.stringify(init.body),
      });
    } catch (cause) {
      throw new OtpRequestError(0, 'network_unavailable', 'The request could not be delivered.');
    }

    let payload: unknown = null;
    try {
      payload = await response.json();
    } catch {
      payload = null;
    }

    const envelope = envelopeSchema.safeParse(payload);

    if (!response.ok || (envelope.success && envelope.data.error !== undefined)) {
      const error = envelope.success ? envelope.data.error : undefined;
      const retryAfterRaw = response.headers.get('retry-after');
      const retryAfterSeconds = retryAfterRaw === null ? null : Number.parseInt(retryAfterRaw, 10);

      throw new OtpRequestError(
        response.status,
        error?.code ?? 'request_failed',
        error?.message ?? (response.statusText || 'The request failed.'),
        error?.request_id ?? '',
        Number.isFinite(retryAfterSeconds) ? retryAfterSeconds : null,
      );
    }

    const data = envelope.success ? envelope.data.data : undefined;

    try {
      return parse(data);
    } catch {
      throw new OtpRequestError(502, 'contract_violation', 'The server response did not match the OTP contract.');
    }
  }

  return {
    /** Public widget bootstrap (widget credentials + policy mirror). */
    loadConfig(): Promise<OtpConfig> {
      return call(paths.config ?? '/otp/config', { method: 'GET' }, (data) => otpConfigSchema.parse(data));
    },

    /** Issue a login-purpose challenge for an identifier. */
    requestChallenge(body: { tenant_id: string; purpose: 'login' | 'signup'; identity: string }): Promise<OtpChallenge> {
      return call(paths.request ?? '/otp', { method: 'POST', body }, (data) => otpChallengeSchema.parse(data));
    },

    /** Issue a signup challenge (profile rides the challenge payload). */
    signupChallenge(body: {
      tenant_id: string;
      phone: string;
      name: string;
      email?: string | null;
      locale?: string | null;
      referral_code?: string | null;
    }): Promise<OtpChallenge> {
      return call(paths.signup ?? '/signup', { method: 'POST', body }, (data) => otpChallengeSchema.parse(data));
    },

    /** Server-gated resend: the 202 carries the channel for widget.retryOtp. */
    resend(body: { tenant_id: string; challenge_id: string }): Promise<OtpResend> {
      return call(paths.resend ?? '/otp/resend', { method: 'POST', body }, (data) => otpResendSchema.parse(data));
    },

    /** Login redeem. 202 mfa_required resolves the held-challenge branch. */
    async redeemLogin(body: RedeemBody): Promise<RedeemResult> {
      return call(paths.login ?? '/login', { method: 'POST', body }, (data) => {
        const object = (data as { object?: string } | null | undefined)?.object;
        if (object === 'customer_session') {
          return { kind: 'session', session: customerSessionSchema.parse(data) } satisfies RedeemResult;
        }
        const challenge = otpChallengeSchema.parse(data);
        if (challenge.status === 'mfa_required') {
          return { kind: 'mfa_required', challenge } satisfies RedeemResult;
        }
        throw new OtpRequestError(502, 'contract_violation', 'Unexpected login response shape.');
      });
    },

    /** Second login phase: TOTP alone on a held challenge. */
    redeemTotp(body: TotpBody): Promise<CustomerSession> {
      return call(paths.login ?? '/login', { method: 'POST', body }, (data) => customerSessionSchema.parse(data));
    },

    /** Signup verification redeem: verifies and mints in one call. */
    redeemVerify(body: { tenant_id: string; challenge_id: string; token: string }): Promise<CustomerSession> {
      return call(paths.verify ?? '/verify', { method: 'POST', body }, (data) => customerSessionSchema.parse(data));
    },

    /** Contact-verification confirm ({id}:confirm). */
    confirmVerification(challengeId: string, body: { token: string }): Promise<unknown> {
      const template = paths.confirm ?? '/verifications/:id:confirm';
      const path = template.replace(':id', encodeURIComponent(challengeId));
      return call(path, { method: 'POST', body }, (data) => data);
    },
  };
}

export type OtpClient = ReturnType<typeof createOtpClient>;
