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

  const now = nowIso !== undefined ? new Date(nowIso) : new Date();
  if (Number.isNaN(now.getTime()) || parsed.getTime() <= now.getTime()) {
    return null;
  }

  return parsed.toISOString();
}
