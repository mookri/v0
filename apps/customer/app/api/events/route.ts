import { newRequestId, err } from '@/application/api-envelope';
import { headersToRecord } from '@/application/actor';
import { createEventStream, sseHeaders } from '@/application/events-stream';
import { retainNotificationFeed, type NotificationItem } from '@/application/events-feed';
import { userChannel } from '@navdhya/notifications/src/events/live';
import { MemoryEventBus } from '@navdhya/notifications/src/adapters/memory-bus';
import { resolveSessionToken } from '@navdhya/identity/src/application/token-session';
import { authSessions, authTokenSecret } from '@/server/auth';
import { laravelEnabled, laravelGet } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Single-instance bus (dev/staging). Multi-instance deployments need a
// shared broker; the EventBus port makes that a swap (see phase 25 notes).
const bus = new MemoryEventBus();

export function __eventsBusForTests() {
  return bus;
}

// SSE captures the bearer when the stream opens. Return the token's
// `exp` (JWT) so the stream can be recycled before it goes stale; opaque
// backend tokens return null and fall back to a fixed recycle window.
function tokenExpiryMs(token: string): number | null {
  const payload = token.split('.')[1];
  if (!payload) return null;
  try {
    const json = Buffer.from(payload.replace(/-/g, '+').replace(/_/g, '/'), 'base64').toString('utf8');
    const exp = (JSON.parse(json) as { exp?: unknown }).exp;
    return typeof exp === 'number' ? exp * 1000 : null;
  } catch {
    return null;
  }
}

export async function GET(req: Request) {
  const requestId = newRequestId();
  const headers = headersToRecord(req.headers);
  const bearer = headers['authorization'];
  if (bearer && !bearer.startsWith('Bearer ')) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let userId: string;
  let backendToken: string;
  if (bearer) {
    // Session-bound handshake: revoked/expired sessions reject here, so
    // logout closes the door on new streams (already-open streams drain).
    const sessionToken = bearer.slice('Bearer '.length).trim();
    let actor;
    try {
      actor = await resolveSessionToken(
        { sessions: authSessions, tokenSecret: authTokenSecret() },
        sessionToken,
        new Date().toISOString(),
      );
    } catch {
      return Response.json(err('UNAUTHENTICATED', 'Invalid session.', requestId), { status: 401 });
    }
    userId = actor.userId;
    backendToken = sessionToken;
  } else if (laravelEnabled()) {
    // Wired deployments (F2b): EventSource cannot set headers, so the
    // stream authenticates with the session cookie like every other
    // wired BFF handler — and that cookie token doubles as the backend
    // bearer the notification feed below reads with.
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    userId = actor.userId;
    backendToken = actor.token;
  } else {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const channel = new URL(req.url).searchParams.get('channel') ?? userChannel(userId);
  if (channel !== userChannel(userId)) {
    return Response.json(err('FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }

  // EventSource reconnects automatically when the server closes the
  // stream. Recycle before the captured bearer expires (30s safety margin)
  // so the reconnect re-authenticates with a fresh cookie; if the token is
  // opaque, recycle every five minutes. Without this, the feed keeps
  // polling with a dead token while heartbeats hide the failure.
  const expiry = tokenExpiryMs(backendToken);
  const recycleAt = expiry ?? Date.now() + 5 * 60_000;
  const recycleInMs = Math.max(1_000, recycleAt - Date.now() - 30_000);
  const streamController = new AbortController();
  const recycleTimer = setTimeout(() => streamController.abort(), recycleInMs);
  const abortOnRequestClose = () => streamController.abort();
  req.signal.addEventListener('abort', abortOnRequestClose, { once: true });
  if (req.signal.aborted) streamController.abort();

  const stream = createEventStream(bus, channel, streamController.signal);

  // First producer for the events route (N2): while the stream is open,
  // tail the caller's backend notifications into the bus — one poll per
  // channel regardless of how many tabs are attached, seeded on retain so
  // history stays the REST list's job. Mock mode has no backend to tail;
  // BFF-side publishers behind the EventBus port land with their flows.
  let releaseFeed: (() => void) | undefined;
  if (laravelEnabled()) {
    releaseFeed = retainNotificationFeed({
      bus,
      channel,
      fetchLatest: async () => {
        const page = await laravelGet<NotificationItem[]>(backendToken, '/customers/notifications', { limit: '50' });
        return page.data ?? [];
      },
    });
  }

  streamController.signal.addEventListener(
    'abort',
    () => {
      clearTimeout(recycleTimer);
      req.signal.removeEventListener('abort', abortOnRequestClose);
      releaseFeed?.();
    },
    { once: true },
  );

  return new Response(stream, { headers: sseHeaders() });
}
