// SLA evaluation for review queues. The clock runs on submittedAt and
// pauses while the ball is in the applicant's court (DRAFT after
// REQUEST_INFO restarts it on resubmit — submittedAt is refreshed then).
export interface SlaState {
  breached: boolean;
  remainingMs: number;
}

export function reviewSlaStatus(submittedAtIso: string, slaHours: number, nowIso: string): SlaState {
  const deadline = Date.parse(submittedAtIso) + slaHours * 3600_000;
  const remainingMs = deadline - Date.parse(nowIso);
  return { breached: remainingMs < 0, remainingMs };
}
