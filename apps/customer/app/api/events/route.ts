import { newRequestId, err } from '@/application/api-envelope';
import { headersToRecord } from '@/application/actor';
import { createEventStream, sseHeaders } from '@/application/events-stream';
import { userChannel } from '@navdhya/notifications/src/events/live';
import { MemoryEventBus } from '@navdhya/notifications/src/adapters/memory-bus';
import { resolveSessionToken } from '@navdhya/identity/src/application/token-session';
import { authSessions, authTokenSecret } from '@/server/auth';

// Single-instance bus (dev/staging). Multi-instance deployments need a
// shared broker; the EventBus port makes that a swap (see phase 25 notes).
const bus = new MemoryEventBus();

export function __eventsBusForTests() {
  return bus;
}

export async function GET(req: Request) {
  const requestId = newRequestId();
  const headers = headersToRecord(req.headers);
  const bearer = headers['authorization'];
  if (!bearer || !bearer.startsWith('Bearer ')) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  // Session-bound handshake: revoked/expired sessions reject here, so logout
  // closes the door on new streams (already-open streams drain).
  let actor;
  try {
    actor = await resolveSessionToken(
      { sessions: authSessions, tokenSecret: authTokenSecret() },
      bearer.slice('Bearer '.length).trim(),
      new Date().toISOString(),
    );
  } catch {
    return Response.json(err('UNAUTHENTICATED', 'Invalid session.', requestId), { status: 401 });
  }
  const channel = new URL(req.url).searchParams.get('channel') ?? userChannel(actor.userId);
  if (channel !== userChannel(actor.userId)) {
    return Response.json(err('FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  const stream = createEventStream(bus, channel, req.signal);
  return new Response(stream, { headers: sseHeaders() });
}
