// /app/api/revalidate/route.ts
// On-demand ISR revalidation endpoint — v2
//
// Fixes:
//   1. Timing-attack: secret comparison now uses timingSafeEqual via a
//      Web Crypto HMAC verify pattern (constant-time).
//   2. Rate limiting: max 30 revalidations per minute per IP.
//   3. Batch revalidation: accepts { slugs: string[] } in addition to { slug }.
//   4. Cache invalidation: calls invalidatePageCache() from graph.ts so the
//      in-process link cache is cleared alongside the Next.js page cache.
//   5. Proper error typing and structured JSON responses.

import { NextRequest, NextResponse } from 'next/server';
import { revalidatePath } from 'next/cache';
import { getRegistryEntry, getClusterMeta } from '@/lib/content/registry';
import { invalidatePageCache } from '@/lib/linking/graph';

// ---------------------------------------------------------------------------
// Rate limiter — simple in-process token bucket (resets on cold start)
// ---------------------------------------------------------------------------

const rateLimitMap = new Map<string, { count: number; resetAt: number }>();
const RATE_LIMIT_MAX = 30;
const RATE_LIMIT_WINDOW_MS = 60_000;

function isRateLimited(ip: string): boolean {
  const now = Date.now();
  const bucket = rateLimitMap.get(ip);

  if (!bucket || now > bucket.resetAt) {
    rateLimitMap.set(ip, { count: 1, resetAt: now + RATE_LIMIT_WINDOW_MS });
    return false;
  }

  if (bucket.count >= RATE_LIMIT_MAX) return true;

  bucket.count += 1;
  return false;
}

// ---------------------------------------------------------------------------
// Timing-safe secret comparison
// Uses Web Crypto HMAC so comparison time is constant regardless of content.
// ---------------------------------------------------------------------------

async function verifySecret(provided: string, expected: string): Promise<boolean> {
  try {
    const enc = new TextEncoder();
    const key = await crypto.subtle.importKey(
      'raw',
      enc.encode(expected),
      { name: 'HMAC', hash: 'SHA-256' },
      false,
      ['sign'],
    );

    // Sign both values with the same key and compare signatures
    const [sigA, sigB] = await Promise.all([
      crypto.subtle.sign('HMAC', key, enc.encode(provided)),
      crypto.subtle.sign('HMAC', key, enc.encode(expected)),
    ]);

    const a = new Uint8Array(sigA);
    const b = new Uint8Array(sigB);
    if (a.length !== b.length) return false;
    let diff = 0;
    for (let i = 0; i < a.length; i++) diff |= a[i] ^ b[i];
    return diff === 0;
  } catch {
    return false;
  }
}

// ---------------------------------------------------------------------------
// Revalidate one slug — returns a result object (never throws)
// ---------------------------------------------------------------------------

interface RevalidateResult {
  slug: string;
  status: 'ok' | 'not_found' | 'error';
  path?: string;
  message?: string;
}

function revalidateSlug(slug: string): RevalidateResult {
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
    invalidatePageCache(slug); // clear in-process link cache too

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
  // Rate limiting
  const ip =
    req.headers.get('x-forwarded-for')?.split(',')[0].trim() ??
    req.headers.get('x-real-ip') ??
    'unknown';

  if (isRateLimited(ip)) {
    return NextResponse.json(
      { message: 'Rate limit exceeded. Max 30 revalidations per minute.' },
      { status: 429 },
    );
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
    for (const s of slugs) {
      if (typeof s === 'string' && s.trim() && !slugList.includes(s.trim())) {
        slugList.push(s.trim());
      }
    }
  }

  if (slugList.length === 0) {
    return NextResponse.json({ message: 'No slugs provided' }, { status: 400 });
  }

  // Revalidate each slug
  const results: RevalidateResult[] = slugList.map(revalidateSlug);

  const allOk = results.every((r) => r.status === 'ok');
  const status = allOk ? 200 : results.some((r) => r.status === 'ok') ? 207 : 422;

  return NextResponse.json(
    {
      revalidated: results.filter((r) => r.status === 'ok').length,
      total: results.length,
      results,
      now: Date.now(),
    },
    { status },
  );
}