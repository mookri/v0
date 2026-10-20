import { LiveEvent } from '@navdhya/notifications/src/events/live';
import { EventBus, LiveListener } from '@navdhya/notifications/src/ports/event-bus';

const BUFFER_PER_CHANNEL = 50;

export class MemoryEventBus implements EventBus {
  private readonly listeners = new Map<string, Set<LiveListener>>();
  private readonly buffer = new Map<string, LiveEvent[]>();

  async publish(event: LiveEvent): Promise<void> {
    const kept = [...(this.buffer.get(event.channel) ?? []), event].slice(-BUFFER_PER_CHANNEL);
    this.buffer.set(event.channel, kept);
    for (const listener of this.listeners.get(event.channel) ?? []) listener(event);
  }

  subscribe(channel: string, listener: LiveListener): () => void {
    let set = this.listeners.get(channel);
    if (!set) {
      set = new Set();
      this.listeners.set(channel, set);
    }
    set.add(listener);
    return () => {
      set!.delete(listener);
    };
  }

  recent(channel: string, limit = 20): LiveEvent[] {
    return (this.buffer.get(channel) ?? []).slice(-limit);
  }
}
