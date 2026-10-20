// /app/api/revalidate/route.ts
// On-demand ISR revalidation endpoint — v3
//
// Fixes vs v2:
//   1. Per-payload rate limit cost (slugs.length), not 1 token per
//      request. Prevents DoS by sending 100K slugs in one request.
//   2. Hard cap on `slugs.length` (100) and on each slug's length
//      (200 chars). Prevents a single big request from skipping the
//      rate limit and exhausting the registry.
//   3. Slug format validation: no leading slash, no `..`, no control
//      chars, no `//`, no `\\`. Stops path-traversal attempts.
//   4. Reuses `lib/security/rate-limit.ts` so the next route that
//      needs this gets it for free.
//   5. Slug deduplication capped at 100 entries (no point deduping
//      beyond the cap — we will reject the request anyway).

import { NextRequest, NextResponse } from 'next/server';
import { revalidatePath, revalidateTag } from 'next/cache';
import { getRegistryEntry, getClusterMeta } from '@/lib/content/registry';
import { invalidatePageCache } from '@/lib/linking/graph';
import { invalidateSearchIndex } from '@/lib/search/federated';
import { InProcessRateLimiter, clientIp } from '@/lib/security/rate-limit';
import { validateCsrfToken, extractCsrfToken } from '@/lib/security/csrf';

// ---------------------------------------------------------------------------
// Rate limiter — per-IP token bucket, charged by slug count.
// ---------------------------------------------------------------------------

const rateLimiter = new InProcessRateLimiter(
  { limit: 30, windowMs: 60_000 },
  { maxKeys: 50_000 },
);

// Hard caps
const MAX_SLUGS_PER_REQUEST = 100;
const MAX_SLUG_LENGTH = 200;
const MAX_BODY_BYTES = 64 * 1024; // 100 slugs x 200 chars ≈ 20 KB; 64 KB is generous
const SLUG_FORMAT = /^[a-z0-9](?:[a-z0-9-]{0,198}[a-z0-9])?$/i;

// ---------------------------------------------------------------------------
// Timing-safe secret comparison — Web Crypto HMAC, constant time.
// ---------------------------------------------------------------------------

async function verifySecret(provided: string, expected: string): Promise<boolean> {
  if (!expected) return false;
  if (provided.length !== expected.length) {
    // Even length-mismatch should be a no-op for the secret.
    // We still run a verify to keep timing flat.
  }
  try {
    const enc = new TextEncoder();
    const paddedExpected = expected.padEnd(provided.length || 1, "\0");
    const key = await crypto.subtle.importKey(
      'raw',
      enc.encode(paddedExpected),
      { name: 'HMAC', hash: 'SHA-256' },
      false,
      ['sign'],
    );
    const sig = await crypto.subtle.sign('HMAC', key, enc.encode(provided));
    const sigB = await crypto.subtle.sign('HMAC', key, enc.encode(paddedExpected));
    const a = new Uint8Array(sig);
    const b = new Uint8Array(sigB);
    if (a.length !== b.length) return false;
    let diff = 0;
    for (let i = 0; i < a.length; i++) diff |= a[i] ^ b[i];
    return diff === 0 && provided.length === expected.length;
  } catch {
    return false;
  }
}

// ---------------------------------------------------------------------------
// Slug validation
// ---------------------------------------------------------------------------

function isValidSlug(slug: string): boolean {
  if (typeof slug !== "string") return false;
  if (slug.length === 0 || slug.length > MAX_SLUG_LENGTH) return false;
  if (slug.startsWith("/") || slug.startsWith("\\")) return false;
  if (slug.includes("//") || slug.includes("\\\\") || slug.includes("..")) return false;
  if (/[\u0000-\u001F\u007F]/.test(slug)) return false;
  return SLUG_FORMAT.test(slug);
}

// ---------------------------------------------------------------------------
// Revalidate one slug — returns a result object (never throws)
// ---------------------------------------------------------------------------

interface RevalidateResult {
  slug: string;
  status: 'ok' | 'not_found' | 'error' | 'invalid';
  path?: string;
  message?: string;
}

function revalidateSlug(slug: string): RevalidateResult {
  if (!isValidSlug(slug)) {
    return { slug, status: 'invalid', message: 'Slug failed format validation' };
  }
  try {
    const entry = getRegistryEntry(slug);
    if (!entry) {
      return { slug, status: 'not_found', message: 'Slug not found in registry' };
    }

    const clusterMeta = getClusterMeta(entry.cluster);
    if (!clusterMeta) {
      return { slug, status: 'error', message: 'Cluster configuration missing' };
    }

    const path = `/${clusterMeta.basePath}/${slug}`;

    revalidatePath(path);
    // Cache Components build: the hourly "use cache" entries in dal/seo.ts
    // are tagged per slug — bust both route shapes on a content edit.
    // ("max" = expire immediately; required second arg on Next 16.)
    revalidateTag(`seo:route:${slug}`, "max");
    revalidateTag(`seo:puja:${slug}`, "max");
    invalidatePageCache(slug);

    return { slug, status: 'ok', path };
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Unknown error';
    return { slug, status: 'error', message };
  }
}

// ---------------------------------------------------------------------------
// Route handler
// ---------------------------------------------------------------------------

export async function POST(req: NextRequest) {
  // Rate limiting — cost = 1 base + (slugs.length) once we know it
  const ip = clientIp(req.headers);
  const early = rateLimiter.check(ip, 1);
  if (!early.allowed) {
    return NextResponse.json(
      { message: 'Rate limit exceeded. Max 30 revalidations per minute.' },
      { status: 429, headers: rateLimiter.toHeaders(early) },
    );
  }

  // CSRF check (P-ARCH follow-up, PR 0.8). The token comes from
  // a cookie issued by `issueCsrfToken()`; the client is expected
  // to echo the same value in the `X-CSRF-Token` header. SameSite=Lax
  // cookies already provide some protection; this is defence in depth.
  // In dev (no CSRF_SECRET), skip the check so the dev tools work.
  if (process.env.NODE_ENV === 'production' || process.env.CSRF_SECRET) {
    const { cookie, header } = extractCsrfToken({
      headers: req.headers,
      cookies: req.cookies,
    });
    const csrf = validateCsrfToken(cookie, header);
    if (!csrf.ok) {
      return NextResponse.json(
        { message: 'CSRF validation failed', reason: csrf.reason },
        { status: 403 },
      );
    }
  }

  // Body size cap — `req.json()` buffers the whole body before
  // validation, so bound it before parsing (contract §8 posture).
  const contentLength = req.headers.get('content-length');
  if (contentLength) {
    const n = Number(contentLength);
    if (Number.isFinite(n) && n > MAX_BODY_BYTES) {
      return NextResponse.json({ message: 'Request body too large' }, { status: 413 });
    }
  }

  let body: unknown;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json({ message: 'Invalid JSON body' }, { status: 400 });
  }

  if (!body || typeof body !== 'object') {
    return NextResponse.json({ message: 'Invalid request body' }, { status: 400 });
  }

  const { slug, slugs, secret } = body as Record<string, unknown>;

  // Auth — timing-safe comparison
  const expectedSecret = process.env.REVALIDATION_SECRET ?? '';

  if (process.env.NODE_ENV === 'production') {
    if (typeof secret !== 'string' || !secret) {
      return NextResponse.json({ message: 'Missing secret' }, { status: 401 });
    }
    const valid = await verifySecret(secret, expectedSecret);
    if (!valid) {
      return NextResponse.json({ message: 'Invalid token' }, { status: 401 });
    }
  } else {
    if (typeof secret === 'string' && expectedSecret && secret !== expectedSecret) {
      console.warn('[ISR] Secret mismatch in development mode — proceeding anyway.');
    }
  }

  // Resolve slug list — supports both { slug: string } and { slugs: string[] }
  const slugList: string[] = [];

  if (typeof slug === 'string' && slug.trim()) {
    slugList.push(slug.trim());
  }

  if (Array.isArray(slugs)) {
    if (slugs.length > MAX_SLUGS_PER_REQUEST) {
      return NextResponse.json(
        { message: `Too many slugs in one request. Max ${MAX_SLUGS_PER_REQUEST}.` },
        { status: 400 },
      );
    }
    for (const s of slugs) {
      if (typeof s !== 'string') continue;
      const t = s.trim();
      if (!t) continue;
      if (slugList.includes(t)) continue;
      slugList.push(t);
    }
  }

  if (slugList.length === 0) {
    return NextResponse.json({ message: 'No slugs provided' }, { status: 400 });
  }

  // Charge the additional cost of the slug count to the rate limiter.
  // The base 1 was already charged above; this represents the
  // "expensive" part of the request.
  const final = rateLimiter.check(ip, slugList.length);
  if (!final.allowed) {
    return NextResponse.json(
      { message: 'Rate limit exceeded. Max 30 revalidations per minute.' },
      { status: 429, headers: rateLimiter.toHeaders(final) },
    );
  }

  // Revalidate each slug
  const results: RevalidateResult[] = slugList.map(revalidateSlug);

  // A content edit changed the BM25 corpus too — drop it so the next
  // search rebuilds from disk (S39; cheap module-level null-out).
  if (results.some((r) => r.status === 'ok')) {
    invalidateSearchIndex();
  }

  const allOk = results.every((r) => r.status === 'ok');
  const status = allOk ? 200 : results.some((r) => r.status === 'ok') ? 207 : 422;

  return NextResponse.json(
    {
      revalidated: results.filter((r) => r.status === 'ok').length,
      invalid: results.filter((r) => r.status === 'invalid').length,
      total: results.length,
      results,
      now: Date.now(),
    },
    { status, headers: rateLimiter.toHeaders(final) },
  );
}