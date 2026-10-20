import { EventBus } from '@navdhya/notifications/src/ports/event-bus';
import { formatSSE, SSE_HEARTBEAT } from '@navdhya/notifications/src/events/live';

// Heartbeat cadence: bare comment lines keep proxies and idle
// intermediaries from closing an otherwise-silent stream.
export const SSE_HEARTBEAT_INTERVAL_MS = 25_000;

// Builds the SSE body stream: buffered events first (Last-Event-ID replay),
// then live publishes. Heartbeats run on an interval for the life of the
// stream; it closes when signal aborts.
export function createEventStream(bus: EventBus, channel: string, signal: AbortSignal): ReadableStream<Uint8Array> {
  const encoder = new TextEncoder();
  let unsubscribe: (() => void) | null = null;
  let heartbeat: ReturnType<typeof setInterval> | null = null;
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
      heartbeat = setInterval(() => {
        try {
          controller.enqueue(encoder.encode(SSE_HEARTBEAT));
        } catch {
          // Consumer gone; cleanup happens via signal.
        }
      }, SSE_HEARTBEAT_INTERVAL_MS);
      (heartbeat as unknown as { unref?: () => void }).unref?.();
      signal.addEventListener('abort', () => {
        unsubscribe?.();
        if (heartbeat !== null) clearInterval(heartbeat);
        heartbeat = null;
        try {
          controller.close();
        } catch {
          // Already closed.
        }
      });
    },
    cancel() {
      unsubscribe?.();
      if (heartbeat !== null) clearInterval(heartbeat);
      heartbeat = null;
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
