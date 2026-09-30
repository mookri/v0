import { newRequestId, err } from '@/application/api-envelope';
import { customerActorResolver, headersToRecord } from '@/application/actor';
import { createEventStream, sseHeaders } from '@/application/events-stream';
import { userChannel } from '@navdhya/notifications/src/events/live';
import { MemoryEventBus } from '@navdhya/notifications/src/adapters/memory-bus';

// Single-instance bus (dev/staging). Multi-instance deployments need a
// shared broker; the EventBus port makes that a swap (see phase 25 notes).
const bus = new MemoryEventBus();
const actors = customerActorResolver();

export function __eventsBusForTests() {
  return bus;
}

export async function GET(req: Request) {
  const requestId = newRequestId();
  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const channel = new URL(req.url).searchParams.get('channel') ?? userChannel(actor.userId);
  if (channel !== userChannel(actor.userId)) {
    return Response.json(err('FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  const stream = createEventStream(bus, channel, req.signal);
  return new Response(stream, { headers: sseHeaders() });
}
