import { ScheduleAssignment, TimeWindow, hasConflict } from './schedule';

export interface ScheduleStore {
  listByResource(resourceId: string): Promise<ScheduleAssignment[]>;
  save(assignment: ScheduleAssignment): Promise<void>;
  update(assignment: ScheduleAssignment): Promise<void>;
}

export type ScheduleResult =
  | { ok: true; assignment: ScheduleAssignment }
  | { ok: false; reason: 'SCHEDULE_CONFLICT' | 'TIME_WINDOW_INVALID' };

export async function commitSchedule(
  store: ScheduleStore,
  input: { id: string; resourceId: string; workOrderId: string; window: TimeWindow; timezone: string },
): Promise<ScheduleResult> {
  if (!(input.window.startsAt < input.window.endsAt)) return { ok: false, reason: 'TIME_WINDOW_INVALID' };
  const existing = await store.listByResource(input.resourceId);
  const windows = existing
    .filter((s) => s.status === 'HELD' || s.status === 'CONFIRMED')
    .map((s) => ({ startsAt: s.startsAt, endsAt: s.endsAt }));
  if (hasConflict(windows, input.window)) return { ok: false, reason: 'SCHEDULE_CONFLICT' };
  const assignment: ScheduleAssignment = {
    id: input.id,
    resourceId: input.resourceId,
    resourceType: 'PARTNER',
    workOrderId: input.workOrderId,
    startsAt: input.window.startsAt,
    endsAt: input.window.endsAt,
    timezone: input.timezone,
    status: 'CONFIRMED',
  };
  await store.save(assignment);
  return { ok: true, assignment };
}
