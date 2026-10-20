// Reschedule input handling (B4 cancel/reschedule UI): the datetime-
// local value becomes an ISO instant the BFF accepts. Fails closed —
// empty, malformed or past values never reach the network; the backend
// remains the authority on lifecycle state and slot availability.

export function rescheduleIsoFromInput(value: string, nowIso?: string): string | null {
  const trimmed = value.trim();
  // datetime-local emits "YYYY-MM-DDTHH:MM" (optionally with seconds).
  if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/.test(trimmed)) {
    return null;
  }

  const parsed = new Date(trimmed);
  if (Number.isNaN(parsed.getTime())) {
    return null;
  }

  // DST spring-forward: a nonexistent wall time (e.g. 02:30) is silently
  // shifted by Date, so round-trip the components and reject any shift.
  // The customer must pick a time that exists in their local timezone.
  const parts = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(trimmed);
  if (
    !parts ||
    parsed.getFullYear() !== Number(parts[1]) ||
    parsed.getMonth() + 1 !== Number(parts[2]) ||
    parsed.getDate() !== Number(parts[3]) ||
    parsed.getHours() !== Number(parts[4]) ||
    parsed.getMinutes() !== Number(parts[5])
  ) {
    return null;
  }

  const now = nowIso !== undefined ? new Date(nowIso) : new Date();
  if (Number.isNaN(now.getTime()) || parsed.getTime() <= now.getTime()) {
    return null;
  }

  return parsed.toISOString();
}
