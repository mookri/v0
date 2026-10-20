// POST /api/search/revalidate — platform ping to bust the in-process
// corpus cache (S63). Guarded by SEARCH_REVALIDATE_SECRET.
import { invalidateSearchIndex } from '@/lib/search/federated';
import { newRequestId, ok, err } from '@/application/api-envelope';
import { timingSafeCompare } from '@/server/federation';

export async function POST(req: Request) {
  const requestId = newRequestId();

  // Fail CLOSED when unconfigured. The previous behaviour (`if (secret)`)
  // meant a deployment that forgot the env var exposed an unauthenticated
  // cache-flush to the internet — an unauthenticated cache-thrash DoS.
  // An unconfigured dependency is unavailable, not wide open; this mirrors
  // the payment webhook, which answers 503 rather than skipping signature
  // verification when RAZORPAY_WEBHOOK_SECRET is absent.
  const secret = process.env.SEARCH_REVALIDATE_SECRET;
  if (!secret) {
    return Response.json(
      err('DEPENDENCY_UNAVAILABLE', 'Search revalidation is not configured.', requestId),
      { status: 503 },
    );
  }

  // Constant-time compare: the secret is a credential, so a byte-by-byte
  // `!==` leaks a matching prefix through response timing. (The shared
  // helper still returns early on a LENGTH mismatch, so an observer can
  // learn the secret's length — never its bytes. That is the right
  // trade-off here: a fully padded compare would mean changing the OIDC
  // state/nonce helper this is shared with.)
  const provided = req.headers.get('x-search-revalidate-secret') ?? '';
  if (!timingSafeCompare(provided, secret)) {
    return Response.json(err('FORBIDDEN', 'Invalid revalidate secret.', requestId), { status: 403 });
  }

  invalidateSearchIndex();
  return Response.json(ok({ invalidated: true }, requestId), { status: 200 });
}