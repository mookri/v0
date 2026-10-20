import { LiveEvent } from '@navdhya/notifications/src/events/live';

export type LiveListener = (event: LiveEvent) => void;

// Channel-scoped fan-out. Memory implementation is single-instance (dev /
// staging behind one server); multi-instance needs a shared broker later.
export interface EventBus {
  publish(event: LiveEvent): Promise<void>;
  subscribe(channel: string, listener: LiveListener): () => void;
  recent(channel: string, limit?: number): LiveEvent[];
}
