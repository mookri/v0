export function scopedIdempotencyKey(input: {
  actorId: string;
  operation: string;
  resourceId: string;
  key: string;
}): string {
  return `${input.actorId}:${input.operation}:${input.resourceId}:${input.key}`;
}

export function samePayloadHash(a: unknown, b: unknown): boolean {
  return JSON.stringify(a) === JSON.stringify(b);
}
