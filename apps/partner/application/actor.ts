import { SignedTokenActorResolver, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';

// Actor resolution for partner BFF routes via signed bearer tokens.
// Secret comes from server env; the insecure fallback is dev-only
// (production must set SESSION_SIGNING_KEY — see packages/config).
// Replaces the former staging header seam; no client ids are trusted.
export function partnerActorResolver(now?: () => string): SignedTokenActorResolver {
  return new SignedTokenActorResolver({
    secret: process.env['SESSION_SIGNING_KEY'] ?? INSECURE_DEV_SECRET,
    now,
  });
}

export function headersToRecord(headers: Headers): Record<string, string | undefined> {
  const out: Record<string, string | undefined> = {};
  headers.forEach((value, key) => {
    out[key.toLowerCase()] = value;
  });
  return out;
}
