import { describe, expect, it } from 'vitest';
import { canCloseIncident, canTransitionIncident } from './incident';
import { canPublishPublicStatus } from './status';

describe('Ceremony Incident & Safety Public Guard', () => {
  it('supports extended ceremony recovery transitions', () => {
    expect(canTransitionIncident('DETECTED', 'AWAITING_VERIFICATION')).toBe(true);
    expect(canTransitionIncident('DECLARED', 'RECOVERY_IN_PROGRESS')).toBe(true);
    expect(canTransitionIncident('MONITORING', 'POSTMORTEM_PENDING')).toBe(true);
    expect(canTransitionIncident('RESOLVED', 'CLOSED')).toBe(true);
  });

  it('blocks incident closure unless cause, outcomes, postmortem and safety signoff are satisfied', () => {
    const blocked = canCloseIncident({
      allAffectedBookingsHaveOutcome: false,
      attributedCauseRecorded: true,
      attributedCause: 'UNKNOWN',
      postmortemCompleted: false,
      severity: 'SEV_0',
      type: 'DEVOTEE_SAFETY_INCIDENT',
      safetyLeadClosed: false,
    });
    expect(blocked.ok).toBe(false);
    expect(blocked.missing).toContain('affected_bookings_outcome');
    expect(blocked.missing).toContain('attributed_cause');
    expect(blocked.missing).toContain('postmortem');
    expect(blocked.missing).toContain('safety_lead_signoff');
  });

  it('never permits public status publication for SEV_0 or safety incidents', () => {
    expect(canPublishPublicStatus({ safetyClassified: true, severity: 'SEV_1', approverId: 'user-1' }).allowed).toBe(false);
    expect(canPublishPublicStatus({ safetyClassified: false, severity: 'SEV_0', approverId: 'user-1' }).allowed).toBe(false);
    expect(canPublishPublicStatus({ safetyClassified: false, severity: 'SEV_2', approverId: '' }).allowed).toBe(false);
    expect(canPublishPublicStatus({ safetyClassified: false, severity: 'SEV_2', approverId: 'ops-1' }).allowed).toBe(true);
  });
});

