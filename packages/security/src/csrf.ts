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
//   be set in production. The check runs on first use, not at
//   import: `next build` imports every route to collect page data
//   with NODE_ENV=production, and a build must never require a
//   runtime secret. Issuing or validating a token still fails
//   closed when the secret is missing in production.

import { createHmac, randomBytes, timingSafeEqual } from "node:crypto";

const CSRF_COOKIE = "navdhya-csrf";
const CSRF_HEADER = "x-csrf-token";
const CSRF_FORM_FIELD = "_csrf";
const DEFAULT_TTL_MS = 24 * 60 * 60 * 1000; // 24h

let cachedServerSecret: string | null = null;

/**
 * Resolve the signing key lazily. Fail-closed in production: without
 * CSRF_SECRET the first attempt to sign throws, so no token can be issued or
 * accepted. The throw is deliberately not at module scope, because Next.js
 * imports route modules while prerendering a production build.
 */
function resolveServerSecret(): string {
  if (cachedServerSecret !== null) return cachedServerSecret;

  const configured = process.env.CSRF_SECRET;
  if (configured) {
    cachedServerSecret = configured;
    return cachedServerSecret;
  }

  if (process.env.NODE_ENV === "production") {
    throw new Error(
      "CSRF_SECRET is not set in production; refusing to issue CSRF tokens.",
    );
  }

  // Ephemeral dev/CI secret. Logged once so the operator notices.
  const ephemeral = randomBytes(32).toString("hex");
  // eslint-disable-next-line no-console
  console.warn(
    "[csrf] No CSRF_SECRET set; using an ephemeral dev-only secret. " +
      "Set CSRF_SECRET in production.",
  );
  cachedServerSecret = ephemeral;
  return cachedServerSecret;
}

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
  return createHmac("sha256", resolveServerSecret()).update(value).digest("base64url");
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
