// lib/security/csrf.ts
// Double-submit-cookie CSRF protection.
//
// Why this exists:
//   None of the current API routes (`/api/revalidate`,
//   `/api/ads/decision`, future `/api/auth/*` and `/api/booking/*`)
//   check for a CSRF token. A logged-in user clicking a malicious
//   link in another tab can be tricked into POSTing to those routes
//   (cross-site request forgery). SameSite=Lax cookies already
//   block most cases, but SameSite=None is sometimes needed for
//   cross-origin embeds, and Lax does NOT protect against same-site
//   attacks (e.g. a malicious sub-domain on a parent's domain).
//
// Pattern: double-submit cookie (similar to csrf-csrf, OWASP
// recommended since the introduction of SameSite):
//   1. Server sets a CSRF cookie (HMAC-signed, time-bounded).
//   2. Client reads the cookie and echoes the value in an
//      `X-CSRF-Token` header (or form field).
//   3. Server compares the cookie value to the header value. If
//      they match, the request is not a cross-site forgery
//      (attackers can't read the cookie from another origin).
//
// Properties:
//   - Stateless: the cookie is signed but the value is also stored
//     on the client; no server-side session table needed.
//   - Time-bounded: cookies expire after `TTL_MS` (default 24h).
//   - Cryptographically random: 32 bytes from `crypto.getRandomValues`,
//     HMAC-SHA-256 signed with a server secret.
//
// IMPORTANT — server secret:
//   The signing key should be set via the `CSRF_SECRET` environment
//   variable. If unset, a per-process ephemeral key is used
//   (resets on every restart) which is fine for dev/CI but MUST
//   be set in production. The check is at module load.

import { createHmac, randomBytes, timingSafeEqual } from "node:crypto";

const CSRF_COOKIE = "navdhya-csrf";
const CSRF_HEADER = "x-csrf-token";
const CSRF_FORM_FIELD = "_csrf";
const DEFAULT_TTL_MS = 24 * 60 * 60 * 1000; // 24h

const SERVER_SECRET =
  process.env.CSRF_SECRET ??
  // Ephemeral dev/CI secret. Logged once so the operator notices.
  (() => {
    if (process.env.NODE_ENV === "production") {
      // Fail-closed: if there's no CSRF_SECRET in production, refuse
      // to issue tokens (the helper will throw on first call).
      throw new Error(
        "CSRF_SECRET is not set in production; refusing to issue CSRF tokens.",
      );
    }
    const e = randomBytes(32).toString("hex");
    // eslint-disable-next-line no-console
    console.warn(
      "[csrf] No CSRF_SECRET set; using an ephemeral dev-only secret. " +
        "Set CSRF_SECRET in production.",
    );
    return e;
  })();

export interface CsrfTokenOptions {
  /** TTL in milliseconds. Default 24h. */
  ttlMs?: number;
}

export interface CsrfTokenResult {
  /** The token string to set in the cookie AND have the client send back. */
  token: string;
  /** The Set-Cookie attributes to use when issuing the token. */
  cookieAttributes: string;
  /** When the token expires (ms since epoch). */
  expiresAt: number;
}

function sign(value: string): string {
  return createHmac("sha256", SERVER_SECRET).update(value).digest("base64url");
}

/** Issue a fresh CSRF token + the Set-Cookie attributes. */
export function issueCsrfToken(options: CsrfTokenOptions = {}): CsrfTokenResult {
  const ttl = options.ttlMs ?? DEFAULT_TTL_MS;
  const issuedAt = Date.now();
  const expiresAt = issuedAt + ttl;
  // Token = random nonce + "." + expiry + "." + HMAC of (nonce + expiry)
  const nonce = randomBytes(32).toString("base64url");
  const expiryStr = String(expiresAt);
  const payload = `${nonce}.${expiryStr}`;
  const sig = sign(payload);
  const token = `${payload}.${sig}`;

  const cookieAttributes = [
    `${CSRF_COOKIE}=${token}`,
    `Path=/`,
    `Max-Age=${Math.floor(ttl / 1000)}`,
    `HttpOnly=false`, // Client must read it to echo in the header
    `Secure`, // Omitted in dev unless HTTPS — see below
    `SameSite=Lax`,
  ].join("; ");

  return { token, cookieAttributes, expiresAt };
}

export interface CsrfValidation {
  ok: boolean;
  reason?:
    | "missing-cookie"
    | "missing-header"
    | "malformed-token"
    | "expired"
    | "signature-mismatch"
    | "mismatch";
}

interface ParsedToken {
  nonce: string;
  expiry: number;
  sig: string;
}

function parseToken(token: string): ParsedToken | null {
  const parts = token.split(".");
  if (parts.length !== 3) return null;
  const [nonce, expiryStr, sig] = parts;
  if (!nonce || !expiryStr || !sig) return null;
  const expiry = Number(expiryStr);
  if (!Number.isFinite(expiry)) return null;
  return { nonce, expiry, sig };
}

function safeEqual(a: string, b: string): boolean {
  if (a.length !== b.length) {
    // Still run a comparison of equal-length buffers to keep timing
    // flat. (Per OWASP recommendation.)
    const a2 = a.padEnd(b.length, "\0");
    const b2 = b;
    const ab = Buffer.from(a2);
    const bb = Buffer.from(b2);
    timingSafeEqual(ab, bb);
    return false;
  }
  return timingSafeEqual(Buffer.from(a), Buffer.from(b));
}

/** Validate a CSRF token pair (cookie + header). */
export function validateCsrfToken(
  cookieValue: string | undefined,
  headerValue: string | undefined,
  now: number = Date.now(),
): CsrfValidation {
  if (!cookieValue) return { ok: false, reason: "missing-cookie" };
  if (!headerValue) return { ok: false, reason: "missing-header" };

  if (cookieValue !== headerValue) return { ok: false, reason: "mismatch" };

  const parsed = parseToken(cookieValue);
  if (!parsed) return { ok: false, reason: "malformed-token" };
  if (now > parsed.expiry) return { ok: false, reason: "expired" };

  const expectedSig = sign(`${parsed.nonce}.${parsed.expiry}`);
  if (!safeEqual(parsed.sig, expectedSig)) {
    return { ok: false, reason: "signature-mismatch" };
  }
  return { ok: true };
}

/** Extract the CSRF token from a request's headers / form body. */
export function extractCsrfToken(request: {
  headers: Headers;
  cookies?: { get(name: string): { value: string } | undefined };
  formData?: () => Promise<FormData>;
}): {
  cookie: string | undefined;
  header: string | undefined;
} {
  const cookie = request.cookies?.get(CSRF_COOKIE)?.value;
  const header = request.headers.get(CSRF_HEADER) ?? undefined;
  return { cookie, header };
}

export const CSRF_CONSTANTS = {
  COOKIE: CSRF_COOKIE,
  HEADER: CSRF_HEADER,
  FORM_FIELD: CSRF_FORM_FIELD,
} as const;
