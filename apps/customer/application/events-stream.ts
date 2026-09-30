import { EventBus } from '@navdhya/notifications/src/ports/event-bus';
import { formatSSE, SSE_HEARTBEAT } from '@navdhya/notifications/src/events/live';

// Builds the SSE body stream: buffered events first (Last-Event-ID replay),
// then live publishes. Heartbeats are emitted by the route on an interval;
// the stream closes when signal aborts.
export function createEventStream(bus: EventBus, channel: string, signal: AbortSignal): ReadableStream<Uint8Array> {
  const encoder = new TextEncoder();
  let unsubscribe: (() => void) | null = null;
  return new ReadableStream<Uint8Array>({
    start(controller) {
      for (const event of bus.recent(channel)) controller.enqueue(encoder.encode(formatSSE(event)));
      unsubscribe = bus.subscribe(channel, (event) => {
        try {
          controller.enqueue(encoder.encode(formatSSE(event)));
        } catch {
          // Consumer gone; cleanup happens via signal.
        }
      });
      signal.addEventListener('abort', () => {
        unsubscribe?.();
        try {
          controller.close();
        } catch {
          // Already closed.
        }
      });
    },
    cancel() {
      unsubscribe?.();
    },
  });
}

export function sseHeaders(): Record<string, string> {
  return {
    'Content-Type': 'text/event-stream',
    'Cache-Control': 'no-store',
    Connection: 'keep-alive',
    'X-Accel-Buffering': 'no',
  };
}

export { SSE_HEARTBEAT };
