// packages/analytics/src/collector/auth.ts
//
// Collector → ingestion signing (plan decision #6): HMAC-SHA256 with
// timestamp + nonce, verified at the ingestion service (GCP Workload
// Identity Federation is unreachable from edge runtimes — this is the
// chosen branch).
//
// Header format (MUST stay byte-compatible with
// services/functions/ingestion/src/worker-auth.ts):
//   x-signals-auth: t=<unix sec>,n=<nonce>,v1=<hex hmac>
//   v1 = HMAC(secret, `${t}.${n}.${sha256hex(body)}`)
//
// Uses Web Crypto (globalThis.crypto.subtle) only — available on Vercel
// Edge, Cloudflare Workers and Node ≥ 18. No node: imports may ever appear
// in this file: portable edge code (plan §0.3 rule 7).

async function sha256Hex(data: string): Promise<string> {
  const digest = await globalThis.crypto.subtle.digest('SHA-256', new TextEncoder().encode(data));
  return [...new Uint8Array(digest)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

async function hmacSha256Hex(secret: string, data: string): Promise<string> {
  const key = await globalThis.crypto.subtle.importKey(
    'raw',
    new TextEncoder().encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const sig = await globalThis.crypto.subtle.sign('HMAC', key, new TextEncoder().encode(data));
  return [...new Uint8Array(sig)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

/** Sign a forward-to-ingestion request; returns the `x-signals-auth` value. */
export async function signCollectorForward(
  secret: string,
  body: string,
  opts: { timestampSec: number; nonce: string },
): Promise<string> {
  const bodyHash = await sha256Hex(body);
  const signature = await hmacSha256Hex(secret, `${opts.timestampSec}.${opts.nonce}.${bodyHash}`);
  return `t=${opts.timestampSec},n=${opts.nonce},v1=${signature}`;
}
