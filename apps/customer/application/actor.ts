import { SignedTokenActorResolver, resolveActorTokenSecret } from '@navdhya/identity/src/application/token-actor';

// Actor resolution for customer BFF routes via signed bearer tokens.
// The secret getter is lazy on purpose: it validates SESSION_SIGNING_KEY at
// request time (throwing in production when unset) rather than at module
// load, which Next.js evaluates during `next build`.
// (production must set SESSION_SIGNING_KEY — see packages/config).
export function customerActorResolver(now?: () => string): SignedTokenActorResolver {
  return new SignedTokenActorResolver({
    secret: () => resolveActorTokenSecret(),
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
