export interface LiveEvent {
  id: string;
  channel: string;
  type: string;
  at: string;
  data: unknown;
}

export function userChannel(userId: string): string {
  return `user:${userId}`;
}

// SSE wire format (server → client). One block per event; heartbeat is a
// bare comment line so intermediaries keep the connection alive.
export function formatSSE(event: LiveEvent): string {
  return `id: ${event.id}\nevent: ${event.type}\ndata: ${JSON.stringify({ channel: event.channel, at: event.at, data: event.data })}\n\n`;
}

export const SSE_HEARTBEAT = ': ping\n\n';

export function parseSSEData(block: string): { channel: string; at: string; data: unknown } {
  const line = block
    .split('\n')
    .map((l) => l.trim())
    .find((l) => l.startsWith('data:'));
  if (!line) throw new Error('SSE_MALFORMED');
  return JSON.parse(line.slice('data:'.length).trim()) as { channel: string; at: string; data: unknown };
}
