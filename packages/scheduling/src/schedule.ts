export type ResourceType = 'PARTNER' | 'VENUE' | 'EQUIPMENT' | 'TEAM';

export interface ScheduleAssignment {
  id: string;
  resourceId: string;
  resourceType: ResourceType;
  workOrderId: string;
  startsAt: string;
  endsAt: string;
  timezone: string;
  status: 'HELD' | 'CONFIRMED' | 'RELEASED' | 'CANCELLED';
}

export interface TimeWindow {
  startsAt: string;
  endsAt: string;
}

export function overlaps(a: TimeWindow, b: TimeWindow): boolean {
  return a.startsAt < b.endsAt && b.startsAt < a.endsAt;
}

export function withBuffer(window: TimeWindow, bufferMinutes: number): TimeWindow {
  const pad = bufferMinutes * 60_000;
  return {
    startsAt: new Date(Date.parse(window.startsAt) - pad).toISOString(),
    endsAt: new Date(Date.parse(window.endsAt) + pad).toISOString(),
  };
}

export function hasConflict(existing: TimeWindow[], candidate: TimeWindow): boolean {
  return existing.some((w) => overlaps(w, candidate));
}
