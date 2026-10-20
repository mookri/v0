// packages/analytics/src/client/batcher.ts
//
// Bounded-size, bounded-latency batching for eligible send attempts.
// Batches never exceed `maxBatch` (GA4/collector request limit: 25 events).
// Delivery outcome handling is the caller's: failed batches are re-queued by
// the owner of the outbox (ack-only-after-positive-acknowledgement).

import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';

export interface BatcherOptions {
  send: (batch: SignalEnvelope[]) => Promise<void>;
  maxBatch?: number;
  /** Latency bound: flush this long after the first item of a batch. */
  maxWaitMs?: number;
  /** Re-queue hook for failed batches (default: drop into nowhere is never OK — required). */
  onFailed: (batch: SignalEnvelope[], error: unknown) => void;
}

export interface Batcher {
  push(env: SignalEnvelope): void;
  /** Force-flush whatever is buffered. Resolves when the send settles. */
  flush(): Promise<void>;
  buffered(): number;
}

export function createBatcher(opts: BatcherOptions): Batcher {
  const maxBatch = opts.maxBatch ?? 25;
  const maxWaitMs = opts.maxWaitMs ?? 5_000;
  let buffer: SignalEnvelope[] = [];
  let timer: ReturnType<typeof setTimeout> | null = null;
  let flushing: Promise<void> = Promise.resolve();

  async function doFlush(): Promise<void> {
    while (buffer.length > 0) {
      const batch = buffer.slice(0, maxBatch);
      buffer = buffer.slice(batch.length);
      try {
        await opts.send(batch);
      } catch (err) {
        opts.onFailed(batch, err);
      }
    }
  }

  function schedule(): void {
    if (buffer.length >= maxBatch) {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
      flushing = flushing.then(() => doFlush());
    } else if (!timer) {
      timer = setTimeout(() => {
        timer = null;
        flushing = flushing.then(() => doFlush());
      }, maxWaitMs);
    }
  }

  return {
    push(env: SignalEnvelope): void {
      buffer.push(env);
      schedule();
    },
    async flush(): Promise<void> {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
      flushing = flushing.then(() => doFlush());
      await flushing;
    },
    buffered(): number {
      return buffer.length;
    },
  };
}
