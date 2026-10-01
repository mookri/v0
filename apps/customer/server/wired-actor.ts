import { err } from '@/application/api-envelope';
import { readCustomerToken, resolveCustomerActor, customerTokenSecret } from '@/server/backend-session';

// Shared wired-actor gate for branched BFF handlers (F2b): resolves the
// cookie actor behind the flag, or returns a 401 Response. Mock paths
// keep using anonymousContext untouched.
export interface WiredActor {
  userId: string;
  sessionId: string;
  token: string;
}

export function wiredActor(req: Request, requestId: string): WiredActor | Response {
  const cookie = req.headers.get('cookie');
  const actor = resolveCustomerActor(cookie, customerTokenSecret());
  if (!actor) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const token = readCustomerToken(cookie);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  return { ...actor, token };
}
